<?php
/**
 * Controller for Leads Kanban full-page module action.
 */
require_once 'include/MVC/Controller/SugarController.php';

class KAN_LeadsKanbanController extends SugarController
{
    public $action_view_map = array(
        'reactkanban' => 'reactkanban',
        'listview' => 'reactkanban',
        'index' => 'reactkanban',
    );

    /**
     * Base controller remaps index -> listview by default.
     * Force both actions to render React Kanban.
     */
    public function action_index()
    {
        $this->view = 'reactkanban';
    }

    public function action_listview()
    {
        $this->view = 'reactkanban';
    }

    public function action_reactkanban()
    {
        $this->view = 'reactkanban';
    }
}

