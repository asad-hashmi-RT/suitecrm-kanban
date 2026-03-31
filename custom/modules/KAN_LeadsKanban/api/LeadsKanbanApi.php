<?php
/**
 * Leads Kanban API entry point.
 *
 * This file is loaded through:
 *   index.php?entryPoint=LeadsKanbanApi
 *
 * Route contract:
 *   GET   /leads-kanban
 *   PATCH /leads-kanban/{lead_id}
 *
 * Query/form params for GET:
 *   - assigned_user_id (optional)
 *   - q (optional; searches name/email)
 *
 * Payload for PATCH:
 *   - to_status (required)
 *
 * Response shape is aligned with the React service normalization layer:
 * {
 *   "columns": [
 *     { "id": "new", "cards": [ ... ] },
 *     ...
 *   ]
 * }
 */
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'custom/modules/KAN_LeadsKanban/api/LeadsKanbanApiService.php';

$service = new LeadsKanbanApiService();
$service->handle();

