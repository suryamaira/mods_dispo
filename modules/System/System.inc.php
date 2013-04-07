<?php
/**
 * System module class
 *
 * Copyright (C) 2009,2010  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

class System extends SimbioModel {
    /**
     * Class contructor
     *
     * @param   object  $simbio: Simbio framework object
     * @return  void
     */
    public function __construct(&$simbio) {
        // get global config from framework
        $this->global = $simbio->getGlobalConfig();
        // get database connection
        $this->dbc = $simbio->getDBC();
    }


    /**
     * Module info
     *
     * @param   object      $simbio: Simbio framework object
     * @return  array       an array of module information
     */
    public static function moduleInfo(&$simbio) {
        return array('module-name' => 'System',
            'module-desc' => 'Manages application wide configuration such as database backup, user and privileges management, etc.',
            'module-depends' => array());
    }


    /**
     * Module privileges information
     *
     * @param   object      $simbio: Simbio framework object
     * @return  array       an array of module privileges
     */
    public static function modulePrivileges(&$simbio) {

    }


    /**
     * Module to check if module installed
     *
     * @param   object  $simbio: Simbio framework object
     * @param   string  $str_module_name: module name to check
     * @return  boolean
     */
    public static function isModuleInstalled(&$simbio, $str_module_name) {
        $_q = $simbio->dbQuery('SELECT COUNT(module_id) FROM {modules} WHERE module_name LIKE \''.$str_module_name.'\'');
        $_d = $_q->fetch_row();
        $_installed = $_d[0];
        if ($_installed > 0) {
            return true;
        }
        return false;
    }
}
