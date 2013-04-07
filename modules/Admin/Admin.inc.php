<?php
/**
 * Admin module class
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

class Admin extends SimbioModel {
    /**
     * Module info
     *
     * @param   object      $simbio: Simbio framework object
     * @return  array       an array of module information
     */
    public static function moduleInfo(&$simbio) {

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
     * Module initialization method
     * All preparation for module such as loading library should be doing here
     *
     * @param   object      $simbio: Simbio framework object
     * @param   string      $str_current_module: current module called by framework
     * @param   string      $str_current_method: current method of current module called by framework
     * @param   string      $str_args: method main argument
     * @return  void
     */
    public function init(&$simbio, $str_current_module, $str_current_method, $str_args) {
        // add Admin module javascript library
        $simbio->addJS(MODULES_WEB_BASE.'Admin/admin.js');
        // get current CLOSURE content
        $_closure = $simbio->getViews('CLOSURE');
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
                // append javascript string to re-register event handler
                $_closure .= '<script type="text/javascript">jQuery(\'#admin-main-content\').unRegisterAdminEvents().registerAdminEvents()</script>';
            }
        }
        // add again to closure
        $simbio->loadView($_closure, 'CLOSURE');
    }
}
