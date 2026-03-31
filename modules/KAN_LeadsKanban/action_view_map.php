<?php
/**
 * Action to view mapping for KAN_LeadsKanban module.
 *
 * SugarController loads this file directly during mapping initialization.
 * This is required because controller class properties are not used as the
 * source of truth for action_view_map in the mapping loader.
 */
$action_view_map = array(
    'reactkanban' => 'reactkanban',
    // default index is remapped to listview by the base controller
    'listview' => 'reactkanban',
    'index' => 'reactkanban',
);

