<?php
/**
 * Module menu entries for Leads Kanban.
 */
if (ACLController::checkAccess('KAN_LeadsKanban', 'list', true)) {
    $module_menu[] = array(
        'index.php?module=KAN_LeadsKanban&action=reactkanban',
        'Leads Kanban',
        'KAN_LeadsKanban',
        'KAN_LeadsKanban'
    );
}

