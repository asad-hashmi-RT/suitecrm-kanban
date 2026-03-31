<?php
/**
 * Service class for Leads Kanban API routing and business logic.
 *
 * Best-practice goals:
 * - No core file edits
 * - ACL checks for Leads list/edit access
 * - Clean JSON responses
 * - Module Loader friendly location under custom/
 */
class LeadsKanbanApiService
{
    /**
     * Fixed Kanban pipeline definition.
     * Key = frontend column id, value = Leads.status value.
     */
    private $statusMap = array(
        'new' => 'New',
        'assigned' => 'Assigned',
        'in_process' => 'In Process',
        'converted' => 'Converted',
        'dead' => 'Dead',
    );

    /**
     * Entry method called by entry point bootstrap.
     */
    public function handle()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $route = $this->resolveRoute();
        $parts = $this->explodeRoute($route);

        // Route: GET /leads-kanban
        if ($method === 'GET' && count($parts) === 1 && $parts[0] === 'leads-kanban') {
            $this->authorizeModuleAction('list');
            $this->handleGetBoard();
        }

        // Route: PATCH /leads-kanban/{id}
        if ($method === 'PATCH' && count($parts) === 2 && $parts[0] === 'leads-kanban') {
            $this->authorizeModuleAction('edit');
            $this->handlePatchLeadStatus($parts[1]);
        }

        $this->jsonError('Route not found', 404);
    }

    /**
     * Resolves route path either from:
     * - query string param: ?route=/leads-kanban
     * - request URI path: /leads-kanban or /index.php/.../leads-kanban
     */
    private function resolveRoute()
    {
        if (!empty($_REQUEST['route'])) {
            return (string) $_REQUEST['route'];
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        // If URL rewrite is set up, this can be direct: /leads-kanban
        if (strpos($path, '/leads-kanban') !== false) {
            return substr($path, strpos($path, '/leads-kanban'));
        }

        return '/';
    }

    /**
     * Splits route into cleaned path segments.
     */
    private function explodeRoute($route)
    {
        $trimmed = trim((string) $route, "/ \t\n\r\0\x0B");
        if ($trimmed === '') {
            return array();
        }

        return array_values(array_filter(explode('/', $trimmed)));
    }

    /**
     * Handles GET /leads-kanban.
     *
     * Example:
     *   index.php?entryPoint=LeadsKanbanApi&route=/leads-kanban&q=john&assigned_user_id=1
     */
    private function handleGetBoard()
    {
        $assignedUserId = isset($_GET['assigned_user_id']) ? trim((string) $_GET['assigned_user_id']) : '';
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

        $whereClauses = array();
        $whereClauses[] = "leads.deleted = 0";

        // Restrict to Kanban statuses only.
        $statusValues = array_values($this->statusMap);
        $quotedStatuses = array_map(array($this, 'quoteForDb'), $statusValues);
        $whereClauses[] = "leads.status IN (" . implode(',', $quotedStatuses) . ")";

        // Optional filter by assigned user.
        if ($assignedUserId !== '') {
            $whereClauses[] = "leads.assigned_user_id = " . $this->quoteForDb($assignedUserId);
        }

        // Optional search by first name, last name, full name, or email1.
        if ($search !== '') {
            $like = $this->quoteForDb('%' . $search . '%');
            $whereClauses[] = "(leads.first_name LIKE {$like}
                OR leads.last_name LIKE {$like}
                OR CONCAT(IFNULL(leads.first_name, ''), ' ', IFNULL(leads.last_name, '')) LIKE {$like}
                OR leads.email1 LIKE {$like})";
        }

        $where = implode(' AND ', $whereClauses);

        /** @var Lead $leadBean */
        $leadBean = BeanFactory::newBean('Leads');
        if (!$leadBean) {
            $this->jsonError('Unable to initialize Leads bean', 500);
        }

        // get_full_list applies ACL/team security through bean framework.
        $rows = $leadBean->get_full_list('leads.date_modified DESC', $where);
        if (!is_array($rows)) {
            $rows = array();
        }

        $columns = $this->buildEmptyColumns();
        foreach ($rows as $lead) {
            if (!$lead || !$lead->ACLAccess('view')) {
                continue;
            }

            $columnId = $this->columnIdFromStatus($lead->status);
            if ($columnId === null) {
                continue;
            }

            $columns[$columnId]['cards'][] = $this->transformLeadToCard($lead);
            $columns[$columnId]['count']++;
        }

        $this->jsonResponse(array(
            'meta' => array(
                'generated_at' => gmdate('c'),
                'statuses' => array_values($this->statusMap),
            ),
            'columns' => array_values($columns),
        ), 200);
    }

    /**
     * Handles PATCH /leads-kanban/{lead_id}.
     *
     * Expected request:
     *   method: PATCH
     *   body: to_status=assigned (column id) OR to_status=Assigned (Leads status value)
     */
    private function handlePatchLeadStatus($leadId)
    {
        $leadId = trim((string) $leadId);
        if ($leadId === '') {
            $this->jsonError('Missing lead id', 400);
        }

        $body = $this->readRequestBody();
        $rawToStatus = isset($body['to_status']) ? trim((string) $body['to_status']) : '';
        if ($rawToStatus === '') {
            $this->jsonError('Missing required field: to_status', 422);
        }

        $normalizedStatus = $this->resolveIncomingStatus($rawToStatus);
        if ($normalizedStatus === null) {
            $this->jsonError('Invalid to_status value', 422);
        }

        /** @var Lead $lead */
        $lead = BeanFactory::getBean('Leads', $leadId);
        if (!$lead || empty($lead->id) || $lead->deleted) {
            $this->jsonError('Lead not found', 404);
        }

        if (!$lead->ACLAccess('view') || !$lead->ACLAccess('save')) {
            $this->jsonError('Access denied for this lead', 403);
        }

        $fromStatus = (string) $lead->status;
        $lead->status = $normalizedStatus;
        $lead->save();

        $this->jsonResponse(array(
            'success' => true,
            'lead_id' => $lead->id,
            'from_status' => $fromStatus,
            'to_status' => $lead->status,
            'date_modified' => $lead->date_modified,
        ), 200);
    }

    /**
     * Converts Leads.status value back to frontend column id.
     */
    private function columnIdFromStatus($status)
    {
        foreach ($this->statusMap as $columnId => $statusValue) {
            if ((string) $status === (string) $statusValue) {
                return $columnId;
            }
        }

        return null;
    }

    /**
     * Accepts either frontend column id or exact CRM status value.
     */
    private function resolveIncomingStatus($value)
    {
        if (isset($this->statusMap[$value])) {
            return $this->statusMap[$value];
        }

        foreach ($this->statusMap as $statusValue) {
            if (strcasecmp((string) $value, (string) $statusValue) === 0) {
                return $statusValue;
            }
        }

        return null;
    }

    /**
     * Builds empty columns in fixed order expected by UI.
     */
    private function buildEmptyColumns()
    {
        return array(
            'new' => array(
                'id' => 'new',
                'title' => 'New Leads',
                'colorVar' => 'var(--kanban-new)',
                'count' => 0,
                'cards' => array(),
            ),
            'assigned' => array(
                'id' => 'assigned',
                'title' => 'Assigned',
                'colorVar' => 'var(--kanban-contacted)',
                'count' => 0,
                'cards' => array(),
            ),
            'in_process' => array(
                'id' => 'in_process',
                'title' => 'In Process',
                'colorVar' => 'var(--kanban-qualified)',
                'count' => 0,
                'cards' => array(),
            ),
            'converted' => array(
                'id' => 'converted',
                'title' => 'Converted',
                'colorVar' => 'var(--kanban-proposal)',
                'count' => 0,
                'cards' => array(),
            ),
            'dead' => array(
                'id' => 'dead',
                'title' => 'Dead',
                'colorVar' => 'var(--kanban-closed)',
                'count' => 0,
                'cards' => array(),
            ),
        );
    }

    /**
     * Transforms a Lead bean into the frontend card contract.
     */
    private function transformLeadToCard($lead)
    {
        $fullName = trim(trim((string) $lead->first_name) . ' ' . trim((string) $lead->last_name));
        if ($fullName === '') {
            $fullName = (string) $lead->name;
        }

        $assignedName = isset($lead->assigned_user_name) ? (string) $lead->assigned_user_name : '';
        if ($assignedName === '' && !empty($lead->assigned_user_id)) {
            $assignedUser = BeanFactory::getBean('Users', $lead->assigned_user_id);
            $assignedName = $assignedUser ? (string) $assignedUser->full_name : '';
        }

        return array(
            'id' => (string) $lead->id,
            'full_name' => $fullName !== '' ? $fullName : 'Unnamed Lead',
            'email' => (string) $lead->email1,
            'company' => (string) $lead->account_name,
            'phone_work' => (string) $lead->phone_work,
            'amount' => 0,
            'priority' => 'low',
            'assigned_user_id' => (string) $lead->assigned_user_id,
            'assigned_user_name' => $assignedName !== '' ? $assignedName : '--',
            'avatar_initials' => $this->makeInitials($assignedName !== '' ? $assignedName : 'U'),
            'date_modified' => (string) $lead->date_modified,
        );
    }

    /**
     * Simple initials helper for avatar placeholder.
     */
    private function makeInitials($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/', $text);
        if (!is_array($parts) || count($parts) === 0) {
            return strtoupper(substr($text, 0, 2));
        }

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }

    /**
     * Module-level ACL guard for Leads.
     */
    private function authorizeModuleAction($action)
    {
        if (!class_exists('ACLController') || !ACLController::checkAccess('Leads', $action, true)) {
            $this->jsonError('Access denied', 403);
        }
    }

    /**
     * Reads request body for PATCH/POST and supports JSON or form-encoded payload.
     */
    private function readRequestBody()
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return array();
        }

        $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string) $_SERVER['CONTENT_TYPE']) : '';
        if (strpos($contentType, 'application/json') !== false) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : array();
        }

        $parsed = array();
        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : array();
    }

    /**
     * Safe DB quoting helper.
     */
    private function quoteForDb($value)
    {
        return $GLOBALS['db']->quoted((string) $value);
    }

    /**
     * JSON success response helper.
     */
    private function jsonResponse($payload, $statusCode = 200)
    {
        http_response_code((int) $statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        sugar_cleanup(true);
    }

    /**
     * JSON error response helper.
     */
    private function jsonError($message, $statusCode = 400)
    {
        $this->jsonResponse(array(
            'success' => false,
            'error' => (string) $message,
        ), (int) $statusCode);
    }
}

