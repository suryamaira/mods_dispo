<?php
/**
 * User module class
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

class User extends SimbioModel {
  protected $global = array();

  /**
   * Method that must be defined by all child module
   * used by framework to get module information
   *
   * @param   object    $simbio: Simbio framework object
   * @return  array     an array of module information containing
   */
  public static function moduleInfo(&$simbio) {
    return array('module-name' => 'Staff',
      'module-desc' => 'Enable application working group member management',
      'module-depends' => array());
  }


  /**
   * Method that must be defined by all child module
   * used by framework to get module privileges type
   *
   * @param   object    $simbio: Simbio framework object
   * @return  array     an array of privileges for this module
   */
  public static function modulePrivileges(&$simbio) {
    return array(
      'add staff',
      'remove staff',
      'update staff',
      'change own profile',
      'add role',
      'remove role',
      'update role'
    );
  }


  /**
   * Class constructor
   *
   * @param   object  $simbio: Simbio framework object
   * @return  void
   */
  public function __construct(&$simbio) {
    // default table
    $this->dbTable = 'staf';
    // get global config from framework
    $this->global = $simbio->getGlobalConfig();
    // get database connection
    $this->dbc = $simbio->getDBC();

    // define user fields
    $this->dbFields['nama'] = array('id' => 'nama', 'label' => __('Nama staf'), 'type' => 'text', 'required' => true);
    $this->dbFields['email'] = array('id' => 'email', 'label' => __('Alamat e-mail'), 'type' => 'text', 'required' => true);
    if ((isset($_SESSION['User']['Priv']['add user']) && isset($_SESSION['User']['Priv']['update user'])) || isset($_SESSION['User']['Admin'])) {
      $this->dbFields['unit_kerja'] = array('id' => 'id_unit', 'label' => __('Unit Kerja'), 'type' => 'dropdown', 'options' => Master::getMasterData($simbio, 'unit_kerja'), 'required' => true);
    }
  }


  /**
   * Method to add module data
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function add(&$simbio, $str_args) {
    if (!User::isUserLogin()) {
      return false;
    }
    if (!$str_args) {
      // create form
      $_form = new FormOutput('user-update', 'index.php?p=staff/save', 'post');
      $_form->submitName = 'add';
      $_form->submitAjax = true;
      $_form->submitValue = __('Add User');
      // add form and set form field value
      foreach ($this->dbFields as $_elm) {
        $_form->add($_elm);
      }
      $simbio->addInfo('USER_ADD_RECORD_INFO', __('You are going to add staf'));
      $simbio->loadView($_form, 'USER_FORM');
    }
  }


  /**
   * Default module page method
   * All module must have this method
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function index(&$simbio, $str_args) {
    if (!User::isUserLogin()) {
      User::login($simbio, $str_args);
      return false;
    }
    if (!(User::isUserLogin('add user') || User::isUserLogin('update user') || User::isUserLogin('remove user'))) {
      return;
    }
    // include datagrid library
    $simbio->loadLibrary('Datagrid', SIMBIO_BASE.'Databases'.DSEP.'Datagrid.inc.php');
    // create datagrid instance
    $_datagrid = new Datagrid($this->dbc);
    // create an array of fields to show in datagrid
    $_fields = array('ID' => 'id_staf', 'Nama Staf' => 'nama', 'E-mail' => 'email',
      __('Input Date') => 'input_date');
    $_primary_keys = array('ID');
    // set column to view in datagrid
    $_datagrid->setSQLColumn($_fields);
    // set primary key for detail view
    $_datagrid->setPrimaryKeys($_primary_keys);
    // set record actions
    $_action['Del.'] = '<input type="checkbox" name="record[]" value="{rowIDs}" />';
    $_action['Edit'] = '<a class="datagrid-links" href="index.php?p=user/update/{rowIDs}"><i class="icon-edit"></i>&nbsp;</a>';
    $_datagrid->setRowActions($_action);
    // set multiple record action options
    $_action_options[] = array('0', 'Pilih tindakan');
    if (User::isUserLogin('remove user')) {
      $_action_options[] = array('user/remove', 'Remove selected users');
    }
    $_datagrid->setActionOptions($_action_options);
    // set result ordering
    $_datagrid->setSQLOrder('input_date DESC');
    // search criteria
    $_criteria = 'user_id<>1';
    if (isset($_GET['keywords'])) {
      $_search = $simbio->filterizeSQLString($_GET['keywords'], true);
      $_criteria .= ' AND (username LIKE \'%'.$_search.'%\' OR realname LIKE \'%'.$_search.'%\')';
    }
    $_datagrid->setSQLCriteria($_criteria);
    // built the datagrid
    $_datagrid->create($this->global['db_prefix'].'users');

    // set header
    $simbio->headerBlockTitle = ucwords('Staff');
    $simbio->headerBlockMenu = array(
        array('class' => 'add', 'link' => 'user/add', 'title' => __('Add New Staff'), 'desc' => __('Add new Staff')),
        array('class' => 'list', 'link' => 'user/manage', 'title' => __('Staff List'), 'desc' => __('View list of existing Staff'))
      );
    // build search form
    $_quick_search = new FormOutput('search', 'index.php', 'get');
    $_quick_search->submitName = 'search';
    $_quick_search->submitValue = __('Search');
    // define form elements
    $_form_items[] = array('id' => 'keywords', 'label' => __('Search '), 'type' => 'text', 'maxSize' => '200');
    $_form_items[] = array('id' => 'p', 'type' => 'hidden', 'value' => 'user/index');
    foreach ($_form_items as $_item) {
      $_quick_search->add($_item);
    }
    $simbio->headerBlockContent = $_quick_search;

    // add to main content
    $simbio->loadView($_datagrid, 'USER_LIST');
  }


  /**
   * Module initialization method
   * All preparation for module such as loading library should be doing here
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_current_module: current module called by framework
   * @param   string    $str_current_method: current method of current module called by framework
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function init(&$simbio, $str_current_module, $str_current_method, $str_args) {
    // get current CLOSURE content
    $_closure = $simbio->getViews('CLOSURE');
    if ($str_current_module == 'admin' || $str_current_module == 'user') {
      $simbio->addJS(MODULES_WEB_BASE.'User/user.js');
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
          // include javascript
          $_closure .= '<script type="text/javascript">jQuery(\'#admin-main-content\').registerUserEvents()</script>';
        }
      }
    }
    // add again to closure
    $simbio->loadView($_closure, 'CLOSURE');
  }


  /**
   * Method to check if user already login
   *
   * @param   mixed     $mix_privileges: an optional argument to check privileges
   * @return  boolean true if user already login and false if otherwise
   */
  public static function isUserLogin($mix_privileges = false) {
    if (isset($_SESSION['User']['ID']) && $_SESSION['User']['Name'] && isset($_SESSION['User']['Priv'])) {
      // only for non-admin user
      if ($_SESSION['User']['ID'] != 1) {
        // check also access privileges
        if ($mix_privileges) {
          if (is_string($mix_privileges)) {
            if (!isset($_SESSION['User']['Priv'][$mix_privileges])) { return false; }
          } else if (is_array($mix_privileges)) {
            foreach ($mix_privileges as $_priv) {
              if (!isset($_SESSION['User']['Priv'][$_priv])) { return false; }
            }
          }
        }
      }
      return true;
    }
    return false;
  }

  /**
   * Method returning an array of application main menu and navigation menu
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @param   string  $str_current_module: current module called by framework
   * @param   string  $str_current_method: current method of current module called by framework
   * @return  array
   */
  public function menu(&$simbio, $str_menu_type = 'navigation', $str_current_module = '', $str_current_method = '') {
    $_menu = array();
    if ($str_menu_type != 'main' && $str_current_module == 'admin' && $str_current_method == 'system') {
      $_menu['System'][] = array('link' => 'user/index', 'name' => __('Users'), 'description' => __('Application user managements'));
      $_menu['System'][] = array('link' => 'user/role', 'name' => __('Roles/Groups'), 'description' => __('Application user role/group managements'));
      $_menu['System'][] = array('link' => 'user/role', 'name' => __('Staff'), 'description' => __('Working group members for tasks delegation'));
    }
    return $_menu;
  }


  /**
   * Method to remove module data
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function profile(&$simbio, $str_args) {
    if (!User::isUserLogin('change own profile')) {
      return false;
    }
    unset($this->dbFields['unit_kerja'], $this->dbFields['roles']);
    $this->update($simbio, $str_args);
    $simbio->addInfo('USER_UPDATE_RECORD_INFO', __('You are going to change your user profile. Don\'t set password if you dont want to change password!'));
  }


  /**
   * Method to remove module data
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function remove(&$simbio, $str_args) {
    if ($str_args != 'role') {
      if (!User::isUserLogin('remove user')) {
        return false;
      }
      if (isset($_POST['record']) && $_POST['record']) {
        $_remove_sql = 'DELETE FROM {users} WHERE user_id IN ';
        $_to_remove = '(';
        foreach ($_POST['record'] as $_rec) {
          $_to_remove .= sprintf('%d,', $_rec);
        }
        $_to_remove = substr_replace($_to_remove, '', -1);
        $_to_remove .= ')';
        // execute SQL
        $_remove_user = $simbio->dbQuery($_remove_sql.$_to_remove);
        if ($_remove_user) {
          $simbio->writeLogs('User', 'Users data deleted', 'USER_REMOVED');
        }
        $simbio->addInfo('USER_REMOVED', __('User data removed from database'));
      }
    } else {
      if (!User::isUserLogin('remove role')) {
        return false;
      }
      if (isset($_POST['record']) && $_POST['record']) {
        $_remove_sql = 'DELETE FROM {roles} WHERE role_id IN ';
        $_remove_access_sql = 'DELETE FROM {role_access} WHERE role_id IN ';
        $_to_remove = '(';
        foreach ($_POST['record'] as $_rec) {
          $_to_remove .= sprintf('%d,', $_rec);
        }
        $_to_remove = substr_replace($_to_remove, '', -1);
        $_to_remove .= ')';
        // execute SQL
        $_remove_role = $simbio->dbQuery($_remove_sql.$_to_remove);
        $_remove_role_access = $simbio->dbQuery($_remove_access_sql.$_to_remove);
        if ($_remove_role) {
          $simbio->writeLogs('User', 'Role data deleted', 'ROLE_REMOVED');
        }
        $simbio->addInfo('ROLE_REMOVED', __('Role data removed from database'));
      }
    }
  }


  /**
   * Manage user roles
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function role(&$simbio, $str_args) {
    if (!User::isUserLogin()) {
      return false;
    }
    if (!$str_args) {
      // include datagrid library
      $simbio->loadLibrary('Datagrid', SIMBIO_BASE.'Databases'.DSEP.'Datagrid.inc.php');
      // create datagrid instance
      $_datagrid = new Datagrid($this->dbc);
      // create an array of fields to show in datagrid
      $_fields = array('ID' => 'role_id', __('Role Name') => 'role_name',
        __('Input Date') => 'input_date');
      $_primary_keys = array('ID');
      // set column to view in datagrid
      $_datagrid->setSQLColumn($_fields);
      // set primary key for detail view
      $_datagrid->setPrimaryKeys($_primary_keys);
      // set record actions
      $_action['Del.'] = '<input type="checkbox" name="record[]" value="{rowIDs}" />';
      $_action['Edit'] = '<a class="datagrid-links" href="index.php?p=user/update/role/{rowIDs}"><i class="icon-edit"></i>&nbsp;</a>';
      $_datagrid->setRowActions($_action);
      // set multiple record action options
      $_action_options[] = array('0', 'Pilih tindakan');
      $_action_options[] = array('user/remove/role', 'Remove selected roles');
      $_datagrid->setActionOptions($_action_options);
      // set result ordering
      $_datagrid->setSQLOrder('input_date DESC');
      // search criteria
      $_criteria = 'role_id<>1';
      if (isset($_GET['keywords'])) {
        $_search = $simbio->filterizeSQLString($_GET['keywords'], true);
        $_criteria .= ' AND role_name LIKE \'%'.$_search.'%\'';
      }
      $_datagrid->setSQLCriteria($_criteria);
      // built the datagrid
      $_datagrid->create($this->global['db_prefix'].'roles');

      // set header
      $simbio->headerBlockTitle = ucwords('Role');
      $simbio->headerBlockMenu = array(
          array('class' => 'add', 'link' => 'user/add/role', 'title' => __('Add New Role'), 'desc' => __('Add new Role')),
          array('class' => 'list', 'link' => 'user/role', 'title' => __('Role List'), 'desc' => __('View list of existing Role'))
        );
      if ($_SESSION['User']['ID'] == 1) {
        $simbio->headerBlockMenu[] = array('class' => 'list', 'link' => 'user/adminpriv', 'title' => __('Update Administrator Privileges'), 'desc' => __('Update Administrator Privileges'));
      }
      // build search form
      $_quick_search = new FormOutput('search', 'index.php', 'get');
      $_quick_search->submitName = 'search';
      $_quick_search->submitValue = __('Search');
      // define form elements
      $_form_items[] = array('id' => 'keywords', 'label' => __('Search '), 'type' => 'text', 'maxSize' => '200');
      $_form_items[] = array('id' => 'p', 'type' => 'hidden', 'value' => 'master');
      foreach ($_form_items as $_item) {
        $_quick_search->add($_item);
      }
      $simbio->headerBlockContent = $_quick_search;

      // add to main content
      $simbio->loadView($_datagrid, 'USER_ROLE_LIST');
    }
  }


  /**
   * Method to save/update module data
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  array     an array of status flag and messages
   */
  public function save(&$simbio, $str_args) {
    if (!User::isUserLogin()) {
      return false;
    }
    //die($str_args);
    if ($str_args != 'role') {
      // save user
      $_data['username'] = $simbio->filterizeSQLString($_POST['username'], true);
      $_data['realname'] = $simbio->filterizeSQLString($_POST['realname'], true);
      if (isset($_POST['roles'])) {
        $_data['roles'] = (isset($_POST['roles']) && $_POST['roles'])?$simbio->filterizeSQLString(serialize($_POST['roles']), true):'NULL';
      }
      if (isset($_POST['id_unit'])) {
        $_data['id_unit'] = (integer)$_POST['id_unit'];
      }
      $_data['last_login'] = '0000-00-00 00:00:00';
      $_data['input_date'] = date('Y-m-d h:i:s');
      $_data['last_update'] = date('Y-m-d h:i:s');
      // do update
      if (isset($_POST['update'])) {
        unset($_data['input_date']);
        $_pswd = trim($_POST['pswd2']);
        if ($_pswd) {
          $_data['pswd'] = sha1($_pswd);
        }
        $_id = (integer)$_POST['updateID'];
        $_update = $simbio->dbUpdate($_data, 'users', 'user_id='.$_id);
      } else if (isset($_POST['add'])) {
        $_pswd = trim($_POST['pswd2']);
        $_data['pswd'] = sha1($_pswd);
        $_update = $simbio->dbInsert($_data, 'users');
      }
      if (!$_update) {
        $simbio->addError('USER_UPDATE_ERROR', __('Failed to update User data. Please contact your system administrator!'));
        $simbio->writeLogs('User', 'Update user '.$_data['realname'].' FAILED', 'USER_UPDATE_ERROR');
      } else {
        if (isset($_POST['update'])) {
          $simbio->addInfo('USER_UPDATE_SUCCESS', __(sprintf('User %s updated', $_data['realname'])));
          $simbio->writeLogs('User', 'Update user '.$_data['realname'], 'USER_UPDATE_SUCCESS');
        } else {
          $simbio->addInfo('USER_INSERT_SUCCESS', __(sprintf('New user %s added to database', $_data['realname'])));
          $simbio->writeLogs('User', 'Insert user '.$_data['realname'], 'USER_INSERT_SUCCESS');
        }
      }
      $this->index($simbio, $str_args);
    } else {
      // save role
      $_data['role_name'] = $simbio->filterizeSQLString($_POST['role_name'], true);
      $_data['input_date'] = date('Y-m-d h:i:s');
      $_data['last_update'] = date('Y-m-d h:i:s');
      // do update
      if (isset($_POST['update'])) {
        unset($_data['input_date']);
        $_id = (integer)$_POST['updateID'];
        $_update = $simbio->dbUpdate($_data, 'roles', 'role_id='.$_id);
      } else if (isset($_POST['add'])) {
        $_update = $simbio->dbInsert($_data, 'roles');
        // get auto ID
        $_id = $simbio->lastInsertID;
      }
      if (!$_update) {
        $simbio->addError('ROLE_UPDATE_ERROR', __('Failed to update Role data. Please contact your system administrator!'));
        $simbio->writeLogs('User', 'Update role '.$_data['role_name'].' FAILED', 'ROLE_UPDATE_ERROR');
      } else {
        // update privileges
        $simbio->dbQuery('DELETE FROM {role_access} WHERE role_id=%d', $_id);
        if (isset($_POST['privs']) && $_POST['privs']) {
          $_curr_date = date('Y-m-d H:i:s');
          $_priv_insert_sql = 'INSERT INTO {role_access} VALUES ';
          foreach ($_POST['privs'] as $_priv) {
            $_priv = $simbio->filterizeSQLString(trim($_priv), true);
            $_priv_insert_sql .= sprintf("($_id, '%s', '$_curr_date', '$_curr_date'),", $_priv);
          }
          // remove last comma
          $_priv_insert_sql = substr_replace($_priv_insert_sql, '', -1);
          $_priv_update = $simbio->dbQuery($_priv_insert_sql);
        }
        if (isset($_POST['update'])) {
          $simbio->addInfo('ROLE_UPDATE_SUCCESS', __(sprintf('Role %s updated', $_data['role_name'])));
          $simbio->writeLogs('User', 'Update role '.$_data['role_name'], 'ROLE_UPDATE_SUCCESS');
        } else {
          $simbio->addInfo('ROLE_INSERT_SUCCESS', __(sprintf('New role %s added to database', $_data['role_name'])));
          $simbio->writeLogs('User', 'Insert role '.$_data['role_name'], 'ROLE_INSERT_SUCCESS');
        }
      }
      $this->role($simbio, $str_args);
    }
  }


  /**
   * Method to update module data
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function update(&$simbio, $str_args) {
    if (!User::isUserLogin()) {
      return false;
    }
    // check update mode
    if (is_int($str_args) || is_numeric($str_args)) {
      // get user detail
      $_user_ID = (integer)$str_args;
      $_user_q = $simbio->dbQuery("SELECT * FROM {users} WHERE user_id=$_user_ID");
      $_d = $_user_q->fetch_assoc();
      // create form
      $_form = new FormOutput('user-update', 'index.php?p=user/save', 'post');
      $_form->submitName = 'update';
      $_form->submitAjax = true;
      $_form->submitValue = __('Update User');
      $_form->includeReset = true;
      $_form->disabled = true;
      $_form->formInfo = '<div class="form-update-buttons btn-group"><a href="#" class="btn form-unlock">'.__('Unlock Form').'</a>'
        .' <a href="#" class="btn form-cancel">'.__('Cancel').'</a>'
        .'</div>';
      // add form and set form field value
      foreach ($this->dbFields as $_elm) {
        if (isset($_d[$_elm['id']])) {
          if ($_elm['id'] == 'roles') {
            $_elm['value'] = @unserialize($_d[$_elm['id']]);
          } else if ($_elm['id'] != 'pswd') {
            $_elm['value'] = $_d[$_elm['id']];
          }
        }
        $_form->add($_elm);
      }
      // add update ID
      $_form->add(array('id' => 'updateID', 'type' => 'hidden', 'value' => $_user_ID));
      $simbio->addInfo('USER_UPDATE_RECORD_INFO', __(sprintf('You are going to update user %s. Don\'t set password if you dont want to change password!', $_d['realname'])));
      $simbio->loadView($_form, 'USER_FORM');
    } else {
      // parse arguments
      $_args = explode('/', $str_args);
      // get role detail
      $_role_ID = isset($_args[1])?$_args[1]:0;
      $_role_q = $simbio->dbQuery("SELECT * FROM {roles} WHERE role_id=%d", $_role_ID);
      $_d = $_role_q->fetch_assoc();
      if ($_role_q->num_rows > 0) {
        // create form
        $_form = new FormOutput('role-update', 'index.php?p=user/save/role', 'post');
        $_form->submitName = 'update';
        $_form->submitAjax = true;
        $_form->submitValue = __('Update Role');
        $_form->includeReset = true;
        $_form->disabled = true;
        $_form->formInfo = '<div class="form-update-buttons btn-group"><a href="#" class="btn form-unlock">'.__('Unlock Form').'</a>'
          .' <a href="#" class="btn form-cancel">'.__('Cancel').'</a>'
          .'</div>';

        // define roles fields
        $_fields['role_name'] = array('id' => 'role_name', 'label' => __('Role Name'), 'type' => 'text', 'required' => true);
        $_fields['privileges'] = array('id' => 'privileges', 'label' => __('Privileges'), 'type' => 'content', 'content' => $this->privilegesList($simbio, $_role_ID));

        // add form and set form field value
        foreach ($_fields as $_elm) {
          if (isset($_d[$_elm['id']])) {
            $_elm['value'] = $_d[$_elm['id']];
          }
          $_form->add($_elm);
        }
        // add update ID
        $_form->add(array('id' => 'updateID', 'type' => 'hidden', 'value' => $_role_ID));
        $simbio->addInfo('ROLE_UPDATE_RECORD_INFO', __('You are going to update role '.$_d['role_name']));
        $simbio->loadView($_form, 'ROLE_FORM');
      } else {
        $simbio->addError('UPDATE_ERROR', __('Error on querying data from database. No Data Found!'));
      }
    }
  }
}
