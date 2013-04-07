<?php
/**
 * Content module class
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

class Content extends SimbioModel {
    /**
     * Class contructor
     *
     * @param   object  $simbio: Simbio framework object
     * @return  void
     */
    public function __construct(&$simbio) {
        $this->dbTable = 'content';
        // get global config from framework
        $this->global = $simbio->getGlobalConfig();
        // get database connection
        $this->dbc = $simbio->getDBC();
    }

    /**
     * Method that must be defined by all child module
     * used by framework to get module information
     *
     * @param   object      $simbio: Simbio framework object
     * @return  array       an array of module information containing
     */
    public static function moduleInfo(&$simbio) {
        return array('module-name' => 'Content',
            'module-desc' => 'Add content management system like capabilities to application',
            'module-depends' => array());
    }


    /**
     * Method that must be defined by all child module
     * used by framework to get module privileges type
     *
     * @param   object      $simbio: Simbio framework object
     * @return  array       an array of privileges for this module
     */
    public static function modulePrivileges(&$simbio) {

    }


    /**
     * Get block of Content
     *
     * @param   object      $obj_db: database connection object
     * @param   string      $str_block_type: type of block
     * @return  string
     */
    public static function getBlock(&$simbio, $str_block_type = '') {
        $_q = $simbio->dbQuery('SELECT content_id, content_title, input_date FROM {content} WHERE content_path IS NULL OR content_path=\'\' LIMIT 10');
        if ($_q->num_rows) {
            $_content_str = '';
            while ($_content = $_q->fetch_assoc()) {
                $_content_str .= '<div class="content-item">'."\n";
                $_content_str .= '<div class="content-title"><a href="?p=content/'.$_content['content_id'].'">'.$_content['content_title'].'</a></div>'."\n";
                $_content_str .= '<div class="content-date">'.$_content['input_date'].'</div>'."\n";
                $_content_str .= '</div>'."\n";
            }
            return $_content_str;
        }
    }


    /**
     * Get Content
     *
     * @param   object      $simbio: Simbio framework instances
     * @param   mixed       $mix_content_path
     * @return  string
     */
    public static function getContent($simbio, $mix_content_path = '') {
        $_q = $simbio->dbQuery('SELECT * FROM {content} WHERE content_path=\''.$mix_content_path.'\' OR content_id=\''.$mix_content_path.'\' LIMIT 1');
        $_content = $_q->fetch_assoc();
        if ($_q->num_rows) {
            $_content_str = '<div class="content">'."\n";
            $_content_str .= '<div class="content-title">'.$_content['content_title'].'</div>'."\n";
            $_content_str .= '<div class="content-body">'.$_content['content_body'].'</div>'."\n";
            $_content_str .= '</div>'."\n";
            return $_content_str;
        }
        return '';
    }


    /**
     * Module initialization method
     * All preparation for module such as loading library should be doing here
     *
     * @param   object  $simbio: Simbio framework object
     * @param   string  $str_current_module: current module called by framework
     * @param   string  $str_current_method: current method of current module called by framework
     * @param   string  $str_args: method main argument
     * @return  void
     */
    public function init(&$simbio, $str_current_module, $str_current_method, $str_args) {
        if (($str_current_module == 'content' || $str_current_module == 'admin')) {
            // add CSS for rich text editor
            $simbio->addCSS(MODULES_WEB_BASE.'Content/jquery.rte.css');
            // add rich text editor library
            $simbio->addJS(MODULES_WEB_BASE.'Content/jquery.rte.js');
            $simbio->addJS(MODULES_WEB_BASE.'Content/jquery.rte.tb.js');
            $simbio->addJS(MODULES_WEB_BASE.'Content/content.js');
            if ($str_current_module == 'content' && $str_current_method == 'manage' && preg_match('@^(add|update)@i', $str_args)) {
                // get current CLOSURE content
                $_closure = $simbio->getViews('CLOSURE');
                $_closure .= '<script type="text/javascript">jQuery(document).ready(function() { initRTE(); })</script>';
                // add again to closure
                $simbio->loadView($_closure, 'CLOSURE');
            }
        }
    }


    /**
     * Rerouting module method
     *
     * @param   object      $simbio: Simbio framework object
     * @param   string      $str_called_method: a method called by framework
     * @param   string      $str_args: method main argument
     * @return  void
     */
    public function reRoute(&$simbio, $str_called_method, $str_args) {
        $str_called_method = $simbio->filterizeSQLString($str_called_method);
        if ($str_called_method == 'manage') {
            if (!$str_args) {
                $str_args = 'index';
            }
            if (preg_match('@\/@i', $str_args)) {
                $_method_args = explode('/', $str_args);
                $_method = isset($_method_args[0])?$_method_args[0]:'index';
                $_method_args = isset($_method_args[1])?$_method_args[1]:'none';
                $this->$_method($simbio, $_method_args);
            } else {
                $this->$str_args($simbio, 'none');
            }
        } else if ($str_called_method == 'index') {
            $this->index($simbio, $str_args);
        } else if ($str_called_method == 'listing') {
            $this->listing($simbio, $str_args);
        } else {
            // get content from file
            $_content_file = LIBS_BASE.'contents'.DSEP.$str_called_method.'.inc.php';
            if (is_numeric($str_called_method)) {
                $str_called_method = (integer)$str_called_method;
                // get content from database
                $_content = $this->getRecords($simbio, array('content_id' => $str_called_method), array('content_title', 'content_body', 'last_update'));
                // get content
                if ($_content) {
                    $_content_str = '<div class="content">'."\n";
                    $_content_str .= '<div class="content-title">'.$_content[0]['content_title'].'</div>'."\n";
                    $_content_str .= '<div class="content-date">'.$_content[0]['last_update'].'</div>'."\n";
                    $_content_str .= '<div class="content-content">'.$_content[0]['content_body'].'</div>';
                    $_content_str .= '</div>';
                    $simbio->setViewConfig('Page Title', $_content[0]['content_title']);
                    $simbio->loadView($_content_str, 'Content');
                }
            } else if (file_exists($_content_file)) {
                ob_start();
                require $_content_file;
                $_content_str = '<div class="content-title">'.$title.'</div>'."\n";
                $_content_str .= '<div class="content-content">'.ob_get_clean().'</div>';
                $simbio->setViewConfig('Page Title', $title);
                $simbio->loadView($_content_str, 'Content');
            } else {
                // get content from database
                $_content = $this->getRecords($simbio, array('content_path' => $str_called_method), array('content_title', 'content_body', 'last_update'));
                // get content
                if ($_content) {
                    $_content_str = '<div class="content">'."\n";
                    $_content_str .= '<div class="content-title">'.$_content[0]['content_title'].'</div>'."\n";
                    $_content_str .= '<div class="content-content">'.$_content[0]['content_body'].'</div>';
                    $_content_str .= '</div>';
                    $simbio->setViewConfig('Page Title', $_content[0]['content_title']);
                    $simbio->loadView($_content_str, 'Content');
                }
            }
        }
    }
}
?>
