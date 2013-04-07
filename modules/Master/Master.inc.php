<?php
/**
 * Master file module class
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

class Master extends SimbioModel {
    public static $tables = array(
        'unit_kerja', 'status', 'staf');
    private $relation = array();
    private $gridMaxField = 5;

    /**
     * Class contructor
     *
     * @param   object  $simbio: Simbio framework object
     * @return  void
     */
    public function __construct(&$simbio) {
        $this->dbTable = self::$tables[0];
        // auto generate fields from database
        $this->autoGenerateFields($simbio);
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
        return array('module-name' => 'Master',
            'module-desc' => 'Master files management',
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
        return array(
            'add master data',
            'remove master data'
        );
    }


    /**
     * Method to add module data
     *
     * @param   object      $simbio: Simbio framework object
     * @param   string      $str_args: method main argument
     * @return  void
     */
    public function add(&$simbio, $str_args) {
        if (!User::isUserLogin()) {
            // return false;
        }
        // master file type
        $this->dbTable = $str_args;
        // auto generate fields from database
        $this->autoGenerateFields($simbio);
        // create form
        $_form = new FormOutput('master-file', 'index.php?p=master/save/'.$this->dbTable, 'post');
        $_form->submitName = 'add';
        $_form->submitValue = __('Simpan');
        $_form->submitAjax = true;
        $_form->formInfo = __('Lengkapi semua ruas mandatori');
        // auto generate form
        $_elms = $this->autoGenerateForm($simbio, $_form);
        // add form and set form field value
        foreach ($_elms as $_elm) {
          if (in_array($_elm['id'], array('id_unit'))) {
            $_elm['label'] = 'Unit Kerja';
            $_elm['type'] = 'dropdown';
            $_elm['options'] = self::getMasterData($simbio, 'unit_kerja');
          }
          $_form->add($_elm);
        }

        $simbio->loadView($_form, 'MASTER_FILE_FORM');
    }


    /**
     * Get master table data
     *
     * @param   object      $simbio: Simbio framework object
     * @return  array       $str_master_table: master table data to fetch
     */
    public static function getMasterData($simbio, $str_master_table) {
      if (in_array($str_master_table, self::$tables)) {
          $_master_data = array();
          $_q = $simbio->dbQuery('SELECT * FROM {'.$str_master_table.'} LIMIT 1');
          // fetch field info
          $_flds = $_q->fetch_fields();
          $_q->close();

          // set sort field
          $_sort_field = $_flds[1]->name;
          $_q = $simbio->dbQuery('SELECT * FROM {'.$str_master_table.'} ORDER BY '.$_sort_field.' LIMIT 1000');
          if ($_q->num_rows > 0) {
              while ($_d = $_q->fetch_row()) {
                  if (isset($_flds[2]->name) && $_flds[2]->name != 'input_date' && isset($_d[2])) {
                      $_master_data[] = array($_d[0], $_d[1].' - '.$_d[2]);
                  } else {
                      $_master_data[] = array($_d[0], $_d[1]);
                  }
              }
          }
          $_q->close();
          return $_master_data;
      }
      return array();
    }


    /**
     * Get master table data
     *
     * @param   object      $simbio: Simbio framework object
     * @return  array       $arr_master_table: master table information to fetch
     */
    public static function getMasterValueByID($simbio, $arr_master_table, $mix_id) {
        if (in_array($arr_master_table['table'], self::$tables)) {
            $_value = '';
            $_q = $simbio->dbQuery('SELECT '.implode(',', $arr_master_table['value_fields']).' FROM {'.$arr_master_table['table'].'} WHERE '.$arr_master_table['primary_key'].'=%d', $mix_id);
            // echo sprintf('SELECT '.implode(',', $arr_master_table['value_fields']).' FROM {'.$arr_master_table['table'].'} WHERE '.$arr_master_table['primary_key'].'=%d', $mix_id);
            // fetch field info
            $_d = $_q->fetch_row(); $_q->close();
            if ($_d && count($_d) > 1) {
                $_value = implode(' - ', $_d);
                return $_value;
            }
            return $_d[0];
        }
        return null;
    }


    /**
     * Automatically create datagrid for master file table
     *
     */
    private function generateDatagrid(&$simbio, $str_args) {
        $_master_tables = $this->global['db_prefix'].$this->dbTable.' AS mst ';

        if ($this->dbTable != 'unit_kerja') {
            $this->relation['id_unit'] = array('table' => 'unit_kerja',
              'display_field' => 'nama_unit', 'pk_field' => 'id_unit');
        }

        // include datagrid library
        $simbio->loadLibrary('Datagrid', SIMBIO_BASE.'Databases'.DSEP.'Datagrid.inc.php');
        // create datagrid instance
        $_datagrid = new Datagrid($this->dbc);
        $_datagrid->numToShow = 20;
        // create an array of fields to show in datagrid
        $_fields = array();
        $_primary_keys = array();
        $_f = 0;
        foreach ($this->dbFields as $_fld => $_fld_info) {
            $_fld_label = ucwords(str_replace('_', ' ', $_fld));
            $_fields[$_fld_label] = 'mst.'.$_fld;
            if (isset($_fld_info['isPrimary'])) {
                $_primary_keys[] = $_fld_label;
            }
            if (isset($this->relation[$_fld])) {
                $_rel_table = $this->global['db_prefix'].$this->relation[$_fld]['table'];
                $_fields[$_fld_label] = $_rel_table.'.'.$this->relation[$_fld]['display_field'];
                $_master_tables .= ' LEFT JOIN `'.$_rel_table.'`
                    ON `mst`.'.$_fld.'='.'`'.$_rel_table.'`.'.$this->relation[$_fld]['pk_field'];
            }
            if ($_f == $this->gridMaxField) {
                break;
            }
            $_f++;
        }
        // set column to view in datagrid
        $_datagrid->setSQLColumn($_fields);
        // set primary key for detail view
        $_datagrid->setPrimaryKeys($_primary_keys);
        // set record actions
        $_action['Del.'] = '<input type="checkbox" name="record[]" value="{rowIDs}" />';
        $_action['Edit'] = '<a class="datagrid-links" href="index.php?p=master/update/'.$this->dbTable.'/{rowIDs}"><b class="icon-edit"></b>&nbsp;</a>';
        $_datagrid->setRowActions($_action);
        // set multiple record action options
        $_action_options[] = array('0', 'Pilih tindakan');
        $_action_options[] = array('master/remove/'.$this->dbTable, 'Hapus rekod terpilih');
        $_datagrid->setActionOptions($_action_options);
        // set result ordering
        $_datagrid->setSQLOrder('mst.input_date DESC');
        // search criteria
        if (isset($_GET['keywords'])) {
            $_search = $simbio->filterizeSQLString($_GET['keywords'], true);
            $_criteria = '';
            $_datagrid->setSQLCriteria($_criteria);
        }
        // built the datagrid
        $_datagrid->create($_master_tables);

        return $_datagrid;
    }


    /**
     * Default module page method
     * All module must have this method
     *
     * @param   object      $simbio: Simbio framework object
     * @param   string      $str_args: method main argument
     * @return  void
     */
    public function index(&$simbio, $str_args) {
        if (!User::isUserLogin()) {
            // return false;
        }
        // master file type
        $this->dbTable = $str_args?$str_args:self::$tables[0];
        // auto generate fields from database
        $this->autoGenerateFields($simbio);
        // create datagrid
        $_datagrid = $this->generateDatagrid($simbio, $str_args);
        // set header
        $simbio->headerBlockTitle = ucwords(str_replace('_', ' ', $this->dbTable));
        $simbio->headerBlockMenu = array(
                array('class' => 'add', 'link' => 'master/add/'.$this->dbTable, 'title' => __('Tambah rekod master'), 'desc' => __('Menambahkan rekod baru untuk data master')),
                array('class' => 'list', 'link' => 'master/index/'.$this->dbTable, 'title' => __('Daftar rekod'), 'desc' => __('Menampilkan daftar semua rekod data master yang ada'))
            );
        // build search form
        $_quick_search = new FormOutput('search', 'index.php', 'get');
        $_quick_search->submitName = 'search';
        $_quick_search->submitValue = __('Cari');
        // define form elements
        $_form_items[] = array('id' => 'keywords', 'label' => __('Cari '), 'type' => 'text', 'maxSize' => '200');
        $_form_items[] = array('id' => 'p', 'type' => 'hidden', 'value' => 'master/index/'.$this->dbTable);
        foreach ($_form_items as $_item) {
            $_quick_search->add($_item);
        }
        $simbio->headerBlockContent = $_quick_search;

        // add to main content
        $simbio->loadView($_datagrid, 'MASTER_FILE_LIST');
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

    }


    /**
     * Method returning an array of application main menu and navigation menu
     *
     * @param   object  $simbio: Simbio framework object
     * @param   string  $str_args: method main argument
     * @param   string  $str_current_module: current module called by framework
     * @param   string  $str_current_method: current method of current module called by framework
     * @return  array
     */
    public function menu(&$simbio, $str_menu_type = 'navigation', $str_current_module = '', $str_current_method = '') {
        $_menu = array();
        if ($str_menu_type == 'main') {
            $_menu[] = array('link' => 'admin/master', 'name' => __('Master File'), 'description' => __('Manajemen tabel data master aplikas Sids.'));
        } else {
            if ($str_current_module == 'admin' && $str_current_method == 'master') {
                foreach (self::$tables as $_master) {
                    $_master_name = ucwords(str_replace('_', ' ', $_master));
                    $_menu['Master File'][] = array('link' => 'master/index/'.$_master, 'name' => $_master_name, 'description' => $_master_name.__(' data management'));
                }
            }
        }
        return $_menu;
    }


    /**
     * Method to update module data
     *
     * @param   object      $simbio: Simbio framework object
     * @param   string      $str_args: method main argument
     * @return  void
     */
    public function update(&$simbio, $str_args) {
        if (!User::isUserLogin()) {
          // return false;
        }
        $_table = self::$tables[0];
        $_data_id = 0;
        list($_table, $_data_id) = explode('/', $str_args);
        // master file type
        $this->dbTable = $_table;
        // auto generate fields from database
        $this->autoGenerateFields($simbio);
        // get primary key field
        $_primary_keys = '';
        foreach ($this->dbFields as $_fld => $_fld_info) {
            if (isset($_fld_info['isPrimary'])) {
                $_primary_keys = $_fld;
            }
        }
        // get record data
        $_rec = $this->getRecords($simbio, array($_primary_keys => $_data_id));
        if (!$_rec) {
            $simbio->addError('RECORD_NOT_FOUND', __("Master file data not found!"));
            return;
        }
        // create form
        $_form = new FormOutput('master-file', 'index.php?p=master/save/'.$this->dbTable, 'post');
        $_form->submitName = 'update';
        $_form->submitAjax = true;
        $_form->submitValue = __('Update');
        $_form->includeReset = true;
        $_form->disabled = true;
        $_form->formInfo = '<div class="form-update-buttons btn-group"><a href="#" class="btn form-unlock">'.__('Aktifkan form').'</a>'
            .' <a href="#" class="btn form-cancel">'.__('Batal').'</a>'
            .'</div>';
        // auto generate form
        $_elms = $this->autoGenerateForm($simbio);
        // add form and set form field value
        foreach ($_elms as $_elm) {

            if (in_array($_elm['id'], array('id_unit'))) {
              $_elm['label'] = 'Unit Kerja';
              $_elm['type'] = 'dropdown';
              $_elm['options'] = self::getMasterData($simbio, 'unit_kerja');
            }

            foreach ($_rec[0] as $_field => $_value) {
                if ($_elm['id'] == $_field) {
                    $_elm['value'] = $_value;
                    $_form->add($_elm);
                }
            }
        }
        // add update ID
        $_form->add(array('id' => 'updateID', 'type' => 'hidden', 'value' => $_data_id));
        $simbio->addInfo('UPDATE_RECORD_INFO', __('Anda akan memperbaharui data Master'));
        $simbio->loadView($_form, 'MASTER_FILE_FORM');
    }


    /**
     * Method to remove module data
     *
     * @param   object      $simbio: Simbio framework object
     * @param   string      $str_args: method main argument
     * @return  void
     */
    public function remove(&$simbio, $str_args) {
        if (!User::isUserLogin()) {
            return false;
        }
        if (isset($_POST['record'])) {
            // master file type
            $this->dbTable = $str_args?$str_args:self::$tables[0];
            // auto generate fields from database
            $this->autoGenerateFields($simbio);
            // get primary key
            $_primary_key = 'id';
            foreach ($this->dbFields as $_fld => $_fld_info) {
                if (isset($_fld_info['isPrimary'])) {
                    $_primary_key = $_fld;
                    break;
                }
            }
            // convert scalar var to array var
            if (!is_array($_POST['record'])) {
                $_POST['record'][0] = $_POST['record'];
            }
            foreach ($_POST['record'] as $_rec_ID) {
                $_rec_ID = (integer)$_rec_ID;
                $simbio->dbDelete("`$_primary_key`='$_rec_ID'", $this->dbTable);
            }
        }
        $this->index($simbio, $str_args);
    }


    /**
     * Method to save/update module data
     *
     * @param   object      $simbio: Simbio framework object
     * @param   string      $str_args: method main argument
     * @return  array       an array of status flag and messages
     */
    public function save(&$simbio, $str_args) {
        if (!User::isUserLogin()) {
          // return false;
        }
        // master file type
        $this->dbTable = $str_args?$str_args:self::$tables[0];
        // auto generate fields from database
        $this->autoGenerateFields($simbio);
        // get db fields
        $_primary_key = 'id';
        foreach ($this->dbFields as $_fld => $_fld_info) {
            if (isset($_fld_info['isPrimary'])) {
                $_primary_key = $_fld;
            }
            if (isset($_POST[$_fld])) {
                $_data[$_fld] = ( preg_match('@.?(char|text|enum)$@i', $_fld_info['dataType']) )?$simbio->filterizeSQLString($_POST[$_fld], true):intval($_POST[$_fld]);
            }
        }
        $_data['input_date'] = date('Y-m-d h:i:s');
        $_data['last_update'] = date('Y-m-d h:i:s');
        // do update
        if (isset($_POST['updateID'])) {
            unset($_data['input_date']);
            $_id = (integer)$_POST['updateID'];
            $_update = $simbio->dbUpdate($_data, $this->dbTable, "`$_primary_key`='$_id'");
        } else {
            $_update = $simbio->dbInsert($_data, $this->dbTable);
        }
        if (!$_update) {
            $_msg = 'Failed to update record for Master file data';
            $simbio->writeLogs('Master', $_msg, 'MASTER_RECORD_UPDATE_ERROR');
            $simbio->addError('MASTER_RECORD_UPDATE_ERROR', $_msg);
        } else {
            $_msg = 'Successfully update Master file data';
            $simbio->writeLogs('Master', $_msg, 'MASTER_RECORD_UPDATED');
            $simbio->addInfo('MASTER_RECORD_UPDATED', $_msg);
        }
        // redirect to master file index page
        $this->index($simbio, $this->dbTable);
    }
}
