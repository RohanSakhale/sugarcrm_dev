<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

class Project extends SugarBean
{
    // database table columns
    public $id;
    public $date_entered;
    public $date_modified;
    public $assigned_user_id;
    public $modified_user_id;
    public $created_by;
    public $name;
    public $description;
    public $deleted;

    // related information
    public $assigned_user_name;
    public $modified_by_name;
    public $created_by_name;

    public $account_id;
    public $contact_id;
    public $opportunity_id;
    public $email_id;
    public $estimated_start_date;

    // calculated information
    public $total_estimated_effort;
    public $total_actual_effort;

    public $object_name = 'Project';
    public $module_dir = 'Project';
    public $new_schema = true;
    public $table_name = 'project';

    // This is used to retrieve related fields from form posts.
    public $additional_column_fields = [
        'account_id',
        'contact_id',
        'opportunity_id',
    ];

    public $relationship_fields = [
        'account_id' => 'accounts',
        'contact_id' => 'contacts',
        'opportunity_id' => 'opportunities',
        'email_id' => 'emails',
    ];

    //////////////////////////////////////////////////////////////////
    // METHODS
    //////////////////////////////////////////////////////////////////

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * overriding the base class function to do a join with users table
     */

    /**
     *
     */
    public function fill_in_additional_detail_fields()
    {
        parent::fill_in_additional_detail_fields();

        $this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
        //$this->total_estimated_effort = $this->_get_total_estimated_effort($this->id);
        //$this->total_actual_effort = $this->_get_total_actual_effort($this->id);
    }

    /**
     *
     */
    public function fill_in_additional_list_fields()
    {
        parent::fill_in_additional_list_fields();
        $this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
        //$this->total_estimated_effort = $this->_get_total_estimated_effort($this->id);
        //$this->total_actual_effort = $this->_get_total_actual_effort($this->id);
    }

    /**
     * Save changes that have been made to a relationship.
     *
     * @param $is_update true if this save is an update.
     */
    public function save_relationship_changes($is_update, $exclude = [])
    {
        parent::save_relationship_changes($is_update, $exclude);
        $new_rel_id = false;
        $new_rel_link = false;
        //this allows us to dynamically relate modules without adding it to the relationship_fields array
        if (!empty($_REQUEST['relate_id']) && !in_array($_REQUEST['relate_to'], $exclude) && $_REQUEST['relate_id'] != $this->id) {
            $new_rel_id = $_REQUEST['relate_id'];
            $new_rel_relname = $_REQUEST['relate_to'];
            if (!empty($this->in_workflow) && !empty($this->not_use_rel_in_req)) {
                $new_rel_id = $this->new_rel_id;
                $new_rel_relname = $this->new_rel_relname;
            }
            $new_rel_link = $new_rel_relname;
            //Try to find the link in this bean based on the relationship
            foreach ($this->field_defs as $key => $def) {
                if (isset($def['type']) && 'link' == $def['type'] && isset($def['relationship']) && $def['relationship'] == $new_rel_relname) {
                    $new_rel_link = $key;
                }
            }
            if ('contacts' == $new_rel_link) {
                $accountId = $this->db->getOne('SELECT account_id FROM accounts_contacts WHERE contact_id=' . $this->db->quoted($new_rel_id));
                if (false !== $accountId) {
                    if ($this->load_relationship('accounts')) {
                        $this->accounts->add($accountId);
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function _get_total_estimated_effort($project_id)
    {
        $return_value = '';

        $query = 'SELECT SUM(' . $this->db->convert('estimated_effort', "IFNULL", 0) . ') total_estimated_effort';
        $query .= ' FROM project_task';
        $query .= " WHERE parent_id='{$project_id}' AND deleted=0";

        $result = $this->db->query($query, true, " Error filling in additional detail fields: ");
        $row = $this->db->fetchByAssoc($result);
        if (null != $row) {
            $return_value = $row['total_estimated_effort'];
        }

        return $return_value;
    }

    /**
     *
     */
    public function _get_total_actual_effort($project_id)
    {
        $return_value = '';

        $query = 'SELECT SUM(' . $this->db->convert('actual_effort', "IFNULL", 0) . ') total_actual_effort';
        $query .= ' FROM project_task';
        $query .= " WHERE parent_id='{$project_id}' AND deleted=0";

        $result = $this->db->query($query, true, " Error filling in additional detail fields: ");
        $row = $this->db->fetchByAssoc($result);
        if (null != $row) {
            $return_value = $row['total_actual_effort'];
        }

        return $return_value;
    }

    /**
     *
     */
    public function get_summary_text()
    {
        return $this->name;
    }

    /**
     *
     */
    public function build_generic_where_clause($the_query_string)
    {
        $where_clauses = [];
        $the_query_string = $GLOBALS['db']->quote($the_query_string);
        array_push($where_clauses, "project.name LIKE '%$the_query_string%'");

        $the_where = '';
        foreach ($where_clauses as $clause) {
            if ('' != $the_where) {
                $the_where .= " OR ";
            }

            $the_where .= $clause;
        }

        return $the_where;
    }

    public function get_list_view_data()
    {
        $field_list = $this->get_list_view_array();
        $field_list['USER_NAME'] = empty($this->user_name) ? '' : $this->user_name;
        $field_list['ASSIGNED_USER_NAME'] = $this->assigned_user_name;
        return $field_list;
    }

    public function bean_implements($interface)
    {
        switch ($interface) {
            case 'ACL':
                return true;
        }
        return false;
    }

    public function create_export_query(&$order_by, &$where, $relate_link_join = '')
    {
        $custom_join = $this->getCustomJoin(true, true, $where);
        $custom_join['join'] .= $relate_link_join;
        $query = "SELECT
				project.*,
                users.user_name as assigned_user_name ";
        $query .= $custom_join['select'];
        $query .= " FROM project ";

        $query .= $custom_join['join'];
        $query .= " LEFT JOIN users
                   	ON project.assigned_user_id=users.id ";

        $where_auto = " project.deleted=0 ";

        if ("" != $where) {
            $query .= "where ($where) AND " . $where_auto;
        } else {
            $query .= "where " . $where_auto;
        }

        if (!empty($order_by)) {
            //check to see if order by variable already has table name by looking for dot "."
            $table_defined_already = strpos($order_by, ".");

            if (false === $table_defined_already) {
                //table not defined yet, define accounts to avoid "ambigous column" SQL error
                $query .= " ORDER BY $order_by";
            } else {
                //table already defined, just add it to end of query
                $query .= " ORDER BY $order_by";
            }
        }
        return $query;
    }

    public function getAllProjectTasks()
    {
        $projectTasks = [];

        $query = "SELECT * FROM project_task WHERE project_id = '" . $this->id . "' AND deleted = 0 ORDER BY project_task_id";
        $result = $this->db->query($query, true, "Error retrieving project tasks");
        $row = $this->db->fetchByAssoc($result);

        while (null != $row) {
            $projectTaskBean = new ProjectTask();
            $projectTaskBean->id = $row['id'];
            $projectTaskBean->retrieve();
            array_push($projectTasks, $projectTaskBean);

            $row = $this->db->fetchByAssoc($result);
        }

        return $projectTasks;
    }
}
