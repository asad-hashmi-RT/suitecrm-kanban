<?php
/**
 * Lightweight bean for the Leads Kanban host module.
 *
 * This module exists to provide a full-page custom view that embeds
 * the React application. It does not manage business records directly.
 */
require_once 'data/SugarBean.php';

class KAN_LeadsKanban extends SugarBean
{
    public $new_schema = true;
    public $module_dir = 'KAN_LeadsKanban';
    public $object_name = 'KAN_LeadsKanban';
    public $table_name = 'kan_leadskanban';
    public $importable = false;
    public $disable_row_level_security = true;
}

