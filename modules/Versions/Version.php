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

 * Description:
 ********************************************************************************/

class Version extends SugarBean
{
    // Stored fields
    public $id;
    public $deleted;
    public $date_entered;
    public $date_modified;
    public $modified_user_id;
    public $created_by;
    public $created_by_name;
    public $modified_by_name;
    public $field_name_map;
    public $name;
    public $file_version;
    public $db_version;
    public $table_name = 'versions';
    public $module_dir = 'Versions';
    public $object_name = "Version";

    public $new_schema = true;

    // This is used to retrieve related fields from form posts.
    public $additional_column_fields = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
    builds a generic search based on the query string using or
    do not include any $this-> because this is called on without having the class instantiated
     */
    public function build_generic_where_clause($the_query_string)
    {
        $where_clauses = [];
        $the_query_string = addslashes($the_query_string);
        array_push($where_clauses, "name like '$the_query_string%'");
        $the_where = "";
        foreach ($where_clauses as $clause) {
            if ("" != $the_where) {
                $the_where .= " or ";
            }

            $the_where .= $clause;
        }

        return $the_where;
    }

    public function is_expected_version($expected_version)
    {
        foreach ($expected_version as $name => $val) {
            if ($this->$name != $val) {
                return false;
            }
        }
        return true;
    }

/**
 * Updates the version info based on the information provided
 */
    public function mark_upgraded($name, $dbVersion, $fileVersion)
    {
        $query = "DELETE FROM versions WHERE name='$name'";
        $GLOBALS['db']->query($query);
        $version = new Version();
        $version->name = $name;
        $version->file_version = $fileVersion;
        $version->db_version = $dbVersion;
        $version->save();

        if (isset($_SESSION['invalid_versions'][$name])) {
            unset($_SESSION['invalid_versions'][$name]);
        }
    }

    public function get_profile()
    {
        return ['name' => $this->name, 'file_version' => $this->file_version, 'db_version' => $this->db_version];
    }
}
