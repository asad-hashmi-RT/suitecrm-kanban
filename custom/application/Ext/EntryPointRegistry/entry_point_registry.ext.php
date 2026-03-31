<?php 
 //WARNING: The contents of this file are auto-generated


/**
 * Registers a custom authenticated entry point for Leads Kanban API.
 *
 * Endpoint base:
 * - index.php?entryPoint=LeadsKanbanApi
 *
 * Supported routes handled by the target file:
 * - GET   /leads-kanban
 * - PATCH /leads-kanban/{lead_id}
 *
 * Notes:
 * - The route can be passed via URL path (if rewrite is configured) or via `route` query param.
 * - Keep this file under custom/Extension for Module Loader compatibility.
 */
$entry_point_registry['LeadsKanbanApi'] = array(
    'file' => 'custom/modules/KAN_LeadsKanban/api/LeadsKanbanApi.php',
    'auth' => true,
);


?>