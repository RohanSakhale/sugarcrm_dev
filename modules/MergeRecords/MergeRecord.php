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

/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

class MergeRecord extends SugarBean
{
    public $object_name = 'MergeRecord';
    public $module_dir = 'MergeRecords';
    public $acl_display_only = true;
    public $merge_module;
    public $merge_bean_class;
    public $merge_bean_file_path;

    public $merge_module2;
    public $merge_bean_class2;
    public $merge_bean_file_path2;

    public $master_id;

    //these arrays store the fields and params to search on
    public $field_search_params = [];

    //this is a object for the bean you are merging on
    public $merge_bean;
    public $merge_bean2;

    //store a copy of the merge bean related strings
    public $merge_bean_strings = [];

    public function __construct($merge_module = '', $merge_id = '')
    {
        global $sugar_config;
        //parent :: __construct();

        if ('' != $merge_module) {
            $this->load_merge_bean($merge_module, $merge_id);
        }
    }

    public function retrieve($id)
    {
        if (isset($_REQUEST['action']) && 'Step2' == $_REQUEST['action']) {
            $this->load_merge_bean($this->merge_bean, false, $id);
        } else {
            parent::retrieve($id);
        }
    }

    public function load_merge_bean($merge_module, $load_module_strings = false, $merge_id = '')
    {
        global $moduleList;
        global $beanList;
        global $beanFiles;
        global $current_language;

        $this->merge_module = $merge_module;
        $this->merge_bean_class = $beanList[$this->merge_module];
        $this->merge_bean_file_path = $beanFiles[$this->merge_bean_class];

        require_once $this->merge_bean_file_path;
        $this->merge_bean = new $this->merge_bean_class();
        if ('' != $merge_id) {
            $this->merge_bean->retrieve($merge_id);
        }

        // Bug 18853 - Disable this view if the user doesn't have edit and delete permissions
        if (!$this->merge_bean->ACLAccess('edit') || !$this->merge_bean->ACLAccess('delete')) {
            ACLController::displayNoAccess();
            sugar_die('');
        }

        //load master module strings
        if ($load_module_strings) {
            $this->merge_bean_strings = return_module_language($current_language, $merge_module);
        }
    }

    // Bug 22994, when the search key words are in other module, there needs to be another merge_bean.
    public function load_merge_bean2($merge_module, $load_module_strings = false, $merge_id = '')
    {
        global $moduleList;
        global $beanList;
        global $beanFiles;
        global $current_language;

        $this->merge_module2 = $merge_module;
        $this->merge_bean_class2 = $beanList[$this->merge_module2];
        $this->merge_bean_file_path2 = $beanFiles[$this->merge_bean_class2];

        require_once $this->merge_bean_file_path2;
        $this->merge_bean2 = new $this->merge_bean_class2();
        if ('' != $merge_id) {
            $this->merge_bean2->retrieve($merge_id);
        }

        //load master module strings
        if ($load_module_strings) {
            $this->merge_bean_strings2 = return_module_language($current_language, $merge_module);
        }
    }

    public $new_schema = true;

    //-----------------------------------------------------------------------
    //-------------Wrapping Necessary Merge Bean Calls-----------------------
    //-----------------------------------------------------------------------

    public function fill_in_additional_list_fields()
    {
        return $this->merge_bean->fill_in_additional_list_fields();
    }

    public function fill_in_additional_detail_fields()
    {
        return $this->merge_bean->fill_in_additional_detail_fields();
    }

    public function get_summary_text()
    {
        return $this->merge_bean->get_summary_text();
    }

    public function get_list_view_data()
    {
        return $this->merge_bean->get_list_view_data();
    }

    //-----------------------------------------------------------------------
    //-----------------------------------------------------------------------
    //-----------------------------------------------------------------------

    /**
    builds a generic search based on the query string using or
    do not include any $this-> because this is called on without having the class instantiated
     */
    public function build_generic_where_clause($the_query_string)
    {
        return $this->merge_bean->build_generic_where_clause($the_query_string);
    }

    //adding in 4.0+ acl function for possible acl stuff down the line
    public function bean_implements($interface)
    {
        switch ($interface) {
            case 'ACL':
                return true;
        }
        return false;
    }

    public function ACLAccess($view, $is_owner = 'not_set')
    {
        global $current_user;

        //if the module doesn't implement ACLS or is empty
        if (empty($this->merge_bean) || !$this->merge_bean->bean_implements('ACL')) {
            return true;
        }

        if ('not_set' == $is_owner) {
            $is_owner = $this->merge_bean->isOwner($current_user->id);
        }
        return ACLController::checkAccess($this->merge_bean->module_dir, 'edit', true);
    }

    //keep save function to handle anything special on merges
    public function save($check_notify = false)
    {
        //something here
        return parent::save($check_notify);
    }

    public function populate_search_params($search_params)
    {
        foreach ($this->merge_bean->field_defs as $key => $value) {
            $searchFieldString = $key . 'SearchField';
            $searchTypeString = $key . 'SearchType';

            if (isset($search_params[$searchFieldString])) {
                if (isset($search_params[$searchFieldString]) == '') {
                    $this->field_search_params[$key]['value'] = 'NULL';
                } else {
                    $this->field_search_params[$key]['value'] = $search_params[$searchFieldString];
                }
                if (isset($search_params[$searchTypeString])) {
                    $this->field_search_params[$key]['search_type'] = $search_params[$searchTypeString];
                } else {
                    $this->field_search_params[$key]['search_type'] = 'Exact';
                }
                //add field_def to the array.
                $this->field_search_params[$key] = array_merge($value, $this->field_search_params[$key]);
            }
        }
    }

    public function get_inputs_for_search_params($search_params)
    {
        $returnString = '';
        foreach ($this->merge_bean->field_defs as $key => $value) {
            $searchFieldString = $key . 'SearchField';
            $searchTypeString = $key . 'SearchType';

            if (isset($search_params[$searchFieldString])) {
                $returnString .= "<input type='hidden' name='$searchFieldString' value='{$search_params[$searchFieldString]}' />\n";
                $returnString .= "<input type='hidden' name='$searchTypeString' value='{$search_params[$searchTypeString]}' />\n";
            }
        }

        return $returnString;
    }

    public function email_addresses_query($table, $module, $bean_id)
    {
        $query = $table . ".id IN (SELECT ear.bean_id FROM email_addresses ea
									LEFT JOIN email_addr_bean_rel ear ON ea.id = ear.email_address_id
									WHERE ear.bean_module = '{$module}'
									AND ear.bean_id != '{$bean_id}'
									AND ear.deleted = 0";
        return $query;
    }

    public function release_name_query($search_type, $value)
    {
        $this->load_merge_bean2('Releases');
        if ('like' == $search_type) {
            $where = "releases.name LIKE '%" . $GLOBALS['db']->quote($value) . "%'";
        } elseif ('start' == $search_type) {
            $where = "releases.name LIKE '" . $GLOBALS['db']->quote($value) . "%'";
        } else {
            $where = "releases.name = '" . $GLOBALS['db']->quote($value) . "'";
        }
        $list = $this->merge_bean2->get_releases(false, 'Active', $where);
        foreach ($list as $key => $value) {
            $list_to_join[] = "'" . $GLOBALS['db']->quote($key) . "'";
        }
        $in = implode(', ', $list_to_join);
        return $in;
    }

    public function create_where_statement()
    {
        $where_clauses = [];
        foreach ($this->field_search_params as $merge_field => $vDefArray) {
            if (isset($vDefArray['source']) && 'custom_fields' == $vDefArray['source']) {
                $table_name = $this->merge_bean->table_name . "_cstm";
            } else {
                $table_name = $this->merge_bean->table_name;
            }

            //Should move these if's into a central location for extensibility and addition for other search filters
            //Must do the same for pulling values in js dropdown
            if (isset($vDefArray['search_type']) && 'like' == $vDefArray['search_type']) {
                if ("email1" != $merge_field && "email2" != $merge_field && "release_name" != $merge_field) {
                    if ('' != $vDefArray['value']) {
                        array_push($where_clauses, $table_name . "." . $merge_field . " LIKE '%" . $GLOBALS['db']->quote($vDefArray['value']) . "%'");
                    }
                } elseif ("release_name" == $merge_field) {
                    if (isset($vDefArray['value'])) {
                        $in = $this->release_name_query('like', $vDefArray['value']);
                        array_push($where_clauses, $table_name . ".found_in_release IN ($in)");
                    }
                } else {
                    $query = $this->email_addresses_query($table_name, $this->merge_module, $this->merge_bean->id);
                    $query .= " AND ea.email_address LIKE '%" . $GLOBALS['db']->quote($vDefArray['value']) . "%')";
                    $where_clauses[] = $query;
                }
            } elseif (isset($vDefArray['search_type']) && 'start' == $vDefArray['search_type']) {
                if ("email1" != $merge_field && "email2" != $merge_field && "release_name" != $merge_field) {
                    array_push($where_clauses, $table_name . "." . $merge_field . " LIKE '" . $GLOBALS['db']->quote($vDefArray['value']) . "%'");
                } elseif ("release_name" == $merge_field) {
                    if (isset($vDefArray['value'])) {
                        $in = $this->release_name_query('start', $vDefArray['value']);
                        array_push($where_clauses, $table_name . ".found_in_release IN ($in)");
                    }
                } else {
                    $query = $this->email_addresses_query($table_name, $this->merge_module, $this->merge_bean->id);
                    $query .= " AND ea.email_address LIKE '" . $GLOBALS['db']->quote($vDefArray['value']) . "%')";
                    $where_clauses[] = $query;
                }
            } else {
                if ("email1" != $merge_field && "email2" != $merge_field && "release_name" != $merge_field) {
                    array_push($where_clauses, $table_name . "." . $merge_field . "='" . $GLOBALS['db']->quote($vDefArray['value']) . "'");
                } elseif ("release_name" == $merge_field) {
                    if (isset($vDefArray['value'])) {
                        $in = $this->release_name_query('exact', $vDefArray['value']);
                        array_push($where_clauses, $table_name . ".found_in_release IN ($in)");
                    }
                } else {
                    $query = $this->email_addresses_query($table_name, $this->merge_module, $this->merge_bean->id);
                    $query .= " AND ea.email_address = '" . $GLOBALS['db']->quote($vDefArray['value']) . "')";
                    $where_clauses[] = $query;
                }
            }
        }
        // Add ACL Check
        if ($this->merge_bean->bean_implements('ACL') && ACLController::requireOwner($this->merge_bean->module_dir, 'delete')) {
            global $current_user;
            $where_clauses[] = $this->merge_bean->getOwnerWhere($current_user->id);
        }
        array_push($where_clauses, $this->merge_bean->table_name . ".id !='" . $GLOBALS['db']->quote($this->merge_bean->id) . "'");
        return $where_clauses;
    }

    //duplicating utils function for now for possiblity of future or/and and
    //other functionality
    public function generate_where_statement($where_clauses)
    {
        $where = '';

        foreach ($where_clauses as $clause) {
            if ("" != $where) {
                $where .= " AND ";
            }

            $where .= $clause;
        }
        return $where;
    }
}
