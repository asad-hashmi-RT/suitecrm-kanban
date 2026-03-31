<?php
/**
 * Primary bean class for KAN_LeadsKanban module.
 *
 * This file exists under modules/ so SuiteCRM can resolve the module directory.
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

