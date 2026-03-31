<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $module_menu;

if (ACLController::checkAccess('Leads', 'list', true)) {
    $module_menu[] = array(
        'index.php?module=KAN_LeadsKanban&action=reactkanban',
        'Leads Kanban',
        'KAN_LeadsKanban',
        'KAN_LeadsKanban'
    );
}

