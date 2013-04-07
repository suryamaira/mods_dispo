<?php
/**
 * Module Disposisi
 *
 * Copyright (C) 2013  Arie Nugraha (dicarve@yahoo.com)
 *
 */

class Disposisi extends SimbioModel {
  protected $global = array();
  /**
   * Class contructor
   *
   * @param   object    $simbio: Simbio framework object
   * @return  void
   */
  public function __construct(&$simbio) {
    // get global config from framework
    $this->global = $simbio->getGlobalConfig();
    // get database connection
    $this->dbc = $simbio->getDBC();
    // ambil data konfigurasi modul disposisi
    $this->config = $simbio->loadConfig('Disposisi');
  }

  public static function cbStatus($obj_db, $_result_row, $obj_sqlgrid) {
    if ($_result_row['Disposisi'] == '1') {
      $_status = '<span class="label label-success">V</span> <a href="./print_disposisi.php?id='.$_result_row['id_surat'].'" class="icon-print" title="Cetak lembar disposisi">&nbsp;</a>';
      // $_status = '<span class="label label-success">V</span> <a href="./index.php?p=Disposisi/cetak/'.$_result_row['id_surat'].'" class="icon-print">&nbsp;</a>';
    } else {
      $_status = '<span class="label label-warning" title="Belum ada">X</span>';
    }

    return $_status;
  }


  public static function cbStatusTanggapan($obj_db, $_result_row, $obj_sqlgrid) {
    if ($_result_row['Tanggapan'] == '1') {
      $_status = '<span class="label label-success">V</span>';
      if ($_SESSION['User']['UnitKerja'] == 0) {
		  $_status .= ' <a href="./print_disposisi.php?tgp='.$_result_row['id_disposisi'].'" class="icon-print" title="Cetak Tanggapan">&nbsp;</a>';
	  }
    } else {
      $_status = '<span class="label label-warning" title="Belum ada">X</span>';
    }

    return $_status;
  }

  /**
   * Module info
   *
   * @param   object    $simbio: Simbio framework object
   * @return  array     an array of module information
   */
  public static function moduleInfo(&$simbio) {
    return array('module-name' => 'Disposisi',
      'module-desc' => 'Disposisi surat',
      'module-depends' => array());
  }

  /**
   * Module privileges definition
   *
   * @param   object  $simbio: Simbio framework object
   * @return  array   an array of privileges for this module
   */
  public static function modulePrivileges(&$simbio) {
    return array(
      'tambah surat',
      'ubah surat',
      'hapus surat',
      'daftar surat',
      'detail surat',
      'disposisi surat',
      'disposisi kode surat',
      'detail disposisi',
      'hapus disposisi',
      'daftar disposisi',
      'beri tanggapan',
      'daftar tanggapan',
      'hapus tanggapan',
      'upload file surat',
      'hapus file surat'
    );
  }

  /**
   * Datagrid data surat masuk
   *
   */
  private function generateDatagrid(&$simbio, $str_args) {
    if (!User::isUserLogin('daftar surat')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    // include datagrid library
    $simbio->loadLibrary('Datagrid', SIMBIO_BASE.'Databases'.DSEP.'Datagrid.inc.php');
    // create datagrid instance
    $_datagrid = new Datagrid($this->dbc);
    $_datagrid->numToShow = 20;
    // create an array of fields to show in datagrid
    $_fields['id_surat'] = 'sm.id_surat';
    $_fields['Nomor'] = 'no_surat';
    $_fields['Perihal'] = 'perihal';
    $_fields['Pengirim'] = 'pengirim';
    $_fields['Tgl. surat'] = 'tgl_surat';
    // $_fields['Tgl. terima'] = 'tgl_terima';
    $_fields['Kode disposisi'] = 'no_disposisi';
    $_fields['Disposisi'] = 'disposisi';
    // set column to view in datagrid
    $_datagrid->setSQLColumn($_fields);
    // set primary key for detail view
    $_datagrid->setPrimaryKeys(array('id_surat'));
    // set record actions
    $_action['Del.'] = '<input type="checkbox" name="record[]" value="{rowIDs}" />';
    if (User::isUserLogin('disposisi surat')) {
      $_action['Edit<br/>Disp.'] = '<a class="datagrid-links" href="index.php?p=disposisi/updatedisposisi/surat/{rowIDs}"><b class="icon-check"></b></a>';
    }
    if (User::isUserLogin('detail surat')) {
      $_action['Detail.'] = '<a class="datagrid-links" href="index.php?p=disposisi/detail/{rowIDs}"><b class="icon-list-detail"></b></a>';
    }
    if (User::isUserLogin('ubah surat')) {
      $_action['Edit'] = '<a class="datagrid-links" href="index.php?p=disposisi/update/{rowIDs}"><b class="icon-pencil"></b></a>';
    }
    $_datagrid->setRowActions($_action);
    // set multiple record action options
    $_action_options[] = array('0', 'Pilih tindakan');
    $_action_options[] = array('disposisi/hapus', 'Hapus rekod terpilih');
    $_datagrid->setActionOptions($_action_options);
    // set result ordering
    $_datagrid->setSQLOrder('input_date DESC');
    // set callback
    $_datagrid->modifyColumnContent('Disposisi', 'callback{Disposisi::cbStatus}');
    // search criteria
    if (isset($_POST['advance'])) { // ADVANCE SEARCH
      $_criteria = 'sm.id_surat > 0 ';
      if ($_POST['no_surat']) {
        $_no_surat = $simbio->filterizeSQLString($_POST['no_surat'], true);
        $_criteria .= " AND sm.no_surat LIKE '%$_no_surat%'";
      }
      if ($_POST['no_disposisi']) {
        $_no_disposisi = $simbio->filterizeSQLString($_POST['no_disposisi'], true);
        $_criteria .= " AND sd.no_disposisi LIKE '%$_no_disposisi%'";
      }
      if ($_POST['perihal']) {
        $_perihal = $simbio->filterizeSQLString($_POST['perihal'], true);
        $_criteria .= " AND sm.perihal LIKE '%$_perihal%'";
      }
      if ($_POST['pengirim']) {
        $_pengirim = $simbio->filterizeSQLString($_POST['pengirim'], true);
        $_criteria .= " AND sm.pengirim LIKE '%$_pengirim%'";
      }
      if ($_POST['kepada']) {
        $_kepada = $simbio->filterizeSQLString($_POST['kepada'], true);
        $_criteria .= " AND sm.kepada LIKE '%$_kepada%'";
      }
      $_datagrid->setSQLCriteria($_criteria);
    } else if (isset($_GET['keywords'])) {
      $_search = $simbio->filterizeSQLString($_GET['keywords'], true);
      $_criteria = "perihal LIKE '%$_search%' OR pengirim LIKE '%$_search%'";
      $_datagrid->setSQLCriteria($_criteria);
    }
    // built the datagrid
    $_datagrid->create('sids_surat_masuk AS sm LEFT JOIN sids_disposisi AS sd ON sm.id_surat=sd.id_surat');

    return $_datagrid;
  }

  /**
   * Datagrid daftar disposisi
   *
   */
  public function daftarDisposisi(&$simbio, $str_args) {
    if (!User::isUserLogin('daftar disposisi')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    $_output = '';
    // include datagrid library
    $simbio->loadLibrary('Datagrid', SIMBIO_BASE.'Databases'.DSEP.'Datagrid.inc.php');
    // create datagrid instance
    $_datagrid = new Datagrid($this->dbc);
    $_datagrid->numToShow = 20;
    // create an array of fields to show in datagrid
    $_fields['id_disposisi'] = 'd.id_disposisi';
    $_fields['No. Disposisi'] = 'no_disposisi';
    $_fields['Perihal'] = 's.perihal';
    $_fields['Status'] = 'st.status';
    $_fields['Tgl. Disposisi'] = 'tgl_disposisi';
    $_fields['Tanggapan'] = 'tanggapan';
    // set column to view in datagrid
    $_datagrid->setSQLColumn($_fields);
    // set primary key for detail view
    $_datagrid->setPrimaryKeys(array('id_disposisi'));
    $_datagrid->sqlGroupBy = 'd.id_disposisi';
    // set record actions
    if (User::isUserLogin('disposisi surat')) {
      $_action['Del.'] = '<input type="checkbox" name="record[]" value="{rowIDs}" />';
    }
    if (User::isUserLogin('detail disposisi')) {
      $_action['Detail.'] = '<a class="datagrid-links" href="index.php?p=disposisi/detaildisposisi/{rowIDs}"><b class="icon-list-detail"></b></a>';
    }
    if (User::isUserLogin('disposisi surat')) {
      $_action['Edit<br />Disp.'] = '<a class="datagrid-links" href="index.php?p=disposisi/updatedisposisi/{rowIDs}"><b class="icon-pencil"></b></a>';
    }
    if (isset($_action)) {
      $_datagrid->setRowActions($_action);
    }
    // set multiple record action options
    $_action_options[] = array('0', 'Pilih tindakan');
    $_action_options[] = array('disposisi/hapusdisposisi', 'Hapus rekod terpilih');
    $_datagrid->setActionOptions($_action_options);
    // set result ordering
    $_datagrid->setSQLOrder('d.id_surat ASC, tgl_disposisi DESC');
    // set callback
    $_datagrid->modifyColumnContent('Tanggapan', 'callback{Disposisi::cbStatusTanggapan}');

    // search criteria
    $_criteria = '';
    if ($str_args) {
    $_id_surat = (integer)$str_args;
    $_criteria = sprintf("d.id_surat=%d", $_id_surat);
      // ambil data surat
      $_surat_q = $simbio->dbQuery('SELECT * FROM {surat_masuk} WHERE id_surat=%d', $_id_surat);
      $_surat_d = $_surat_q->fetch_assoc();
      $simbio->addInfo('DISPOSISI_DAFTAR', sprintf('Daftar disposisi untuk surat dengan perihal <strong class="perihal">%s</strong>
      yang dikirim oleh <strong class="pengirim">%s</strong>', $_surat_d['perihal'], $_surat_d['pengirim']));

      $_output .= '<div class="tombols"><a class="btn" href="index.php?p=disposisi/updatedisposisi/'.$_id_surat.'"><li class="icon-plus"></li> Tambah Disposisi</a></div>';
    }
    if (isset($_GET['keywords'])) {
      $_search = $simbio->filterizeSQLString($_GET['keywords'], true);
      $_criteria .= sprintf("p.perihal LIKE '%%%s%%'", $_search);
    }
    if (isset($_SESSION['User']['UnitKerja'])) {
      if ((integer)$_SESSION['User']['UnitKerja'] > 0) {
        $_criteria .= sprintf("du.id_unit=%d", $_SESSION['User']['UnitKerja']);
      }
    }
    $_datagrid->setSQLCriteria($_criteria);
    // built the datagrid
    $_datagrid->create('sids_disposisi AS d
      LEFT JOIN sids_status AS st ON d.status=st.id_status
      LEFT JOIN sids_surat_masuk AS s ON d.id_surat=s.id_surat
      LEFT JOIN sids_disposisi_unit_kerja AS du ON d.id_disposisi=du.id_disposisi');

    $_output .= $_datagrid->build();

    $simbio->loadView($_output, 'Disposisi');
  }

  /**
   * Datagrid daftar tanggapan
   *
   */
  public function daftarTanggapan(&$simbio, $str_args) {
    if (!User::isUserLogin('daftar tanggapan')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    $_output = '';
    // include datagrid library
    $simbio->loadLibrary('Datagrid', SIMBIO_BASE.'Databases'.DSEP.'Datagrid.inc.php');
    // create datagrid instance
    $_datagrid = new Datagrid($this->dbc);
    $_datagrid->numToShow = 20;
    // create an array of fields to show in datagrid
    $_fields['id_tanggapan'] = 't.id_tanggapan';
    $_fields['No Surat'] = 's.no_surat';
    $_fields['No. Disposisi'] = 'd.no_disposisi';
    $_fields['Perihal'] = 's.perihal';
    $_fields['Tanggapan'] = 't.tanggapan';
    $_fields['Tgl. Disposisi'] = 'd.tgl_disposisi';
    // set column to view in datagrid
    $_datagrid->setSQLColumn($_fields);
    // set primary key for detail view
    $_datagrid->setPrimaryKeys(array('id_tanggapan'));
    // set record actions
    $_action['Del.'] = '<input type="checkbox" name="record[]" value="{rowIDs}" />';
    $_action['Dtl.'] = '<a class="datagrid-links" href="index.php?p=disposisi/detaildisposisi/{rowIDs}"><b class="icon-list-detail"></b></a>';
    $_action['Edit'] = '<a class="datagrid-links" href="index.php?p=disposisi/updatedisposisi/0/{rowIDs}"><b class="icon-pencil"></b></a>';
    $_datagrid->setRowActions($_action);
    // set multiple record action options
    $_action_options[] = array('0', 'Pilih tindakan');
    $_action_options[] = array('disposisi/hapusdisposisi', 'Hapus rekod terpilih');
    $_datagrid->setActionOptions($_action_options);
    // set result ordering
    $_datagrid->setSQLOrder('t.id_tanggapan ASC, d.tgl_disposisi DESC');

    // search criteria
    $_criteria = '';
    if ($str_args) {
      $_id_surat = (integer)$str_args;
      $_criteria = sprintf("d.id_surat=%d", $_id_surat);
        // ambil data surat
        $_surat_q = $simbio->dbQuery('SELECT * FROM {surat_masuk} WHERE id_surat=%d', $_id_surat);
        $_surat_d = $_surat_q->fetch_assoc();
        $simbio->addInfo('DISPOSISI_DAFTAR', sprintf('Daftar tanggapan untuk surat No <strong class="pengirim">%s</strong> dengan perihal <strong class="perihal">%s</strong> ',
			$_surat_d['no_surat'], $_surat_d['perihal']));

        $_output .= '<div class="tombols"><a class="btn" href="index.php?p=disposisi/updatedisposisi/'.$_id_surat.'"><li class="icon-plus"></li> Tambah Disposisi</a></div>';
    }
    if (isset($_GET['keywords'])) {
      $_search = $simbio->filterizeSQLString($_GET['keywords'], true);
      $_criteria .= sprintf("p.perihal LIKE '%%%s%%'", $_search);
    }
    $_datagrid->setSQLCriteria($_criteria);
    // built the datagrid
    $_datagrid->create('sids_tanggapan_unit AS t
      LEFT JOIN sids_disposisi AS d ON d.id_disposisi=t.id_disposisi
      LEFT JOIN sids_surat_masuk AS s ON s.id_surat=d.id_surat');

    $_output .= $_datagrid->build();

    $simbio->loadView($_output, 'Disposisi');
  }

  /**
   * Ambil data file yang terhubung dengan surat tertentu
   *
   */
  private function dataFileSurat(&$simbio, $str_args) {
    $_data = array();
    $_id_surat = (integer)$str_args;
    $_file_surat_q = $simbio->dbQuery('SELECT sf.id_surat, f.* FROM {surat_file} AS sf
    LEFT JOIN {file_surat} AS f ON sf.id_file=f.id_file WHERE sf.id_surat=%d', $_id_surat);
    while ($_data_surat = $_file_surat_q->fetch_assoc()) {
    $_data[] = $_data_surat;
    }
    return $_data;
  }

  /**
   * Ambil data tanggapan yang terhubung dengan disposisi tertentu
   *
   */
  private function dataTanggapanDisposisi(&$simbio, $str_args) {
    $_data = array();
    $_id_disposisi = (integer)$str_args;
    $_tanggapan_q = $simbio->dbQuery('SELECT tgp.*, d.no_disposisi, uk.* FROM {tanggapan_unit} AS tgp
    LEFT JOIN {disposisi} AS d ON tgp.id_disposisi=d.id_disposisi
    LEFT JOIN {unit_kerja} AS uk ON tgp.id_unit=uk.id_unit
    WHERE tgp.id_disposisi=%d', $_id_disposisi);
    while ($_data_tanggapan = $_tanggapan_q->fetch_assoc()) {
    $_data[] = $_data_tanggapan;
    }
    return $_data;
  }

  /**
   * Ambil data unit kerja yang terhubung dengan disposisi tertentu
   *
   */
  private function dataUnitKerjaDisposisi(&$simbio, $str_args) {
    $_data = array();
    $_id_disposisi = (integer)$str_args;
    $_unitkerja_q = $simbio->dbQuery('SELECT duk.*, d.no_disposisi, uk.nama_unit FROM {disposisi_unit_kerja} AS duk
    LEFT JOIN {disposisi} AS d ON duk.id_disposisi=d.id_disposisi
    LEFT JOIN {unit_kerja} AS uk ON duk.id_unit=uk.id_unit
    WHERE duk.id_disposisi=%d', $_id_disposisi);
    while ($_data_unitkerja = $_unitkerja_q->fetch_assoc()) {
      $_data[] = $_data_unitkerja;
    }
    return $_data;
  }


  private function dataStafUnitKerjaDisposisi(&$simbio, $str_args) {
    $_data = array();
    $_id_unit = (integer)$str_args;
    $_staf_q = $simbio->dbQuery('SELECT suk.*, uk.nama_unit FROM {staf} AS suk
    LEFT JOIN {unit_kerja} AS uk ON suk.id_unit=uk.id_unit');
    // WHERE suk.id_unit=%d', $_id_unit);
    while ($_data_staf = $_staf_q->fetch_assoc()) {
      $_data[] = $_data_staf;
    }
    return $_data;
  }

  /**
   * Detail surat
   *
   */
  public function detail(&$simbio, $str_args) {
    if (!User::isUserLogin('detail surat')) {
      User::login($simbio, $str_args);
      return false;
    }
    $_output = '';
    $_id_surat = (integer)trim($str_args);
    // ambil data surat
    $_surat_q = $simbio->dbQuery('SELECT * FROM {surat_masuk} WHERE id_surat=%d', $_id_surat);
    $_surat_d = $_surat_q->fetch_assoc();
    $simbio->addInfo('SURAT_DETAIL', sprintf('Detail surat masuk <strong class="no_surat">No. %s</strong> dengan perihal <strong class="perihal">%s</strong>
      yang dikirim oleh <strong class="pengirim">%s</strong> ', $_surat_d['no_surat'], $_surat_d['perihal'], $_surat_d['pengirim']));

    $_output .= '<div class="tombols btn-group">';
    if (User::isUserLogin('disposisi surat')) {
      $_disposisi_q = $simbio->dbQuery('SELECT count(*) FROM {disposisi} WHERE id_surat=%d', $_id_surat);
	  $_disposisi_d = $_disposisi_q->fetch_array();
	  if ($_disposisi_d[0]<0) {
        $_output .= '<a class="btn" href="index.php?p=disposisi/updatedisposisi/'.$_id_surat.'"><li class="icon-chevron-up"></li> Tambah Disposisi</a> ';
	  }
    }
    if (User::isUserLogin('ubah surat')) {
      $_output .= '<a class="btn" href="index.php?p=disposisi/update/'.$_id_surat.'"><li class="icon-pencil"></li> Ubah Data Surat</a>';
    }
    $_output .= '</div>';

    // detail
    $_detail = '<div class="well well-small">';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('No Surat').'</div><div class="span8 detail-content">'.$_surat_d['no_surat'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Pengirim').'</div><div class="span8 detail-content">'.$_surat_d['pengirim'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Kepada').'</div><div class="span8 detail-content">'.$_surat_d['kepada'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Perihal').'</div><div class="span8 detail-content">'.$_surat_d['perihal'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Tanggal surat').'</div><div class="span8 detail-content">'.$_surat_d['tgl_surat'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Tanggal terima').'</div><div class="span8 detail-content">'.$_surat_d['tgl_terima'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Tanggal proses').'</div><div class="span8 detail-content">'.$_surat_d['tgl_proses'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('File surat').'</div><div class="span8 detail-content">';

    // query data file terkait surat ke database
    $_detail .= '<table id="daftarFile" class="table table-striped table-bordered table-condensed">';
    foreach ($this->dataFileSurat($simbio, $_id_surat) as $_data_surat) {
      $_detail .= '<tr><td>'.$_data_surat['namafile'].'</td><td>'.( round($_data_surat['file_size']/(1024*1024), 2) ).' MB</td><td><a class="btn btn-mini btn-info" href="./files/surat/'.$_data_surat['namafile'].'">Baca/Lihat</a></td></tr>';
    }
    $_detail .= '</table>';

    $_detail .= '</div></div>';
    $_detail .= '</div>';

    $_output .= $_detail;


    $simbio->loadView($_output, 'Detail Surat');
  }

  /**
   * Detail data disposisi
   *
   */
  public function detailDisposisi(&$simbio, $str_args) {
    if (!User::isUserLogin('detail disposisi')) {
      User::login($simbio, $str_args);
      return false;
    }
    $_output = '';
    $_id_disposisi = (integer)trim($str_args);
    $_disposisi_q = $simbio->dbQuery('SELECT d.*, st.status AS `Status` FROM {disposisi} AS d
      LEFT JOIN {status} AS st ON d.status=st.id_status
      WHERE id_disposisi=%d', $_id_disposisi);
    $_disposisi_d = $_disposisi_q->fetch_assoc();

    // ambil data surat
    $_surat_q = $simbio->dbQuery('SELECT * FROM {surat_masuk} WHERE id_surat=%d', $_disposisi_d['id_surat']);
    $_surat_d = $_surat_q->fetch_assoc();
    $simbio->addInfo('DISPOSISI_DETAIL', sprintf('Detail disposisi untuk surat dengan perihal <strong class="perihal">%s</strong>
      yang dikirim oleh <strong class="pengirim">%s</strong>', $_surat_d['perihal'], $_surat_d['pengirim']));

    $_output .= '<div class="tombols btn-group">';
    if (User::isUserLogin('beri tanggapan')) {
      $_output .= '<a class="btn" href="#isitanggapan"><b class="icon-comment"></b> Berikan Tanggapan</a> ';
    }
    if (User::isUserLogin('disposisi surat')) {
      $_output .= '<a class="btn" href="index.php?p=disposisi/updatedisposisi/'.$_id_disposisi.'"><b class="icon-pencil"></b> Ubah Disposisi</a>';
    }
    $_output .= '</div>';

    // detail
    $_detail = '<div class="well well-small">';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Nomor Surat').'</div><div class="span8 detail-content">'.$_surat_d['no_surat'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Kode Disposisi').'</div><div class="span8 detail-content">'.$_disposisi_d['no_disposisi'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Perintah').'</div><div class="span8 detail-content">'.$_disposisi_d['perintah'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Status Disposisi').'</div><div class="span8 detail-content">'.$_disposisi_d['Status'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Unit Kerja Disposisi').'</div><div class="span8 detail-content">';
    $_unit_kerja_disp = $this->dataUnitKerjaDisposisi($simbio, $_id_disposisi);
    if (count($_unit_kerja_disp) > 0) {
      $_detail .= '<ul class="unit_kerja_disposisi">';
      // unit kerja penerima disposisi
      foreach ($this->dataUnitKerjaDisposisi($simbio, $_id_disposisi) as $uk) {
      $_detail .= '<li>'.$uk['nama_unit'].'</li>';
      }
      $_detail .= '</ul>';
    } else {
      $_detail .= '<div class="alert alert-error">Tidak ada unit kerja yang menerima disposisi ini</div>';
    }

    $_detail .= '</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('Tanggal Disposisi').'</div><div class="span8 detail-content">'.$_disposisi_d['tgl_disposisi'].'</div></div>';
    $_detail .= '<div class="row"><div class="span3 detail-label">'.__('File surat').'</div><div class="span8 detail-content">';

    // query data file terkait surat ke database
    $_detail .= '<table id="daftarFile" class="table table-striped table-bordered table-condensed">';
    foreach ($this->dataFileSurat($simbio, $_disposisi_d['id_surat']) as $_data_surat) {
      $_detail .= '<tr><td>'.$_data_surat['namafile'].'</td><td>'.( round($_data_surat['file_size']/(1024*1024), 2) ).' MB</td><td><a class="btn btn-mini btn-info" href="./files/surat/'.$_data_surat['namafile'].'">Baca/Lihat</a></td></tr>';
    }
    $_detail .= '</table>';

    $_detail .= '</div></div>';
    $_detail .= '</div>';

    $_output .= $_detail;

    if (User::isUserLogin('beri tanggapan')) {
      $_output .= '<a name="isitanggapan"></a>';
      $_output .= $this->updateTanggapan($simbio, $str_args);
    }

    // daftar tanggapan
    $_tanggapan = '';
    $_data_tanggapan = $this->dataTanggapanDisposisi($simbio, $_id_disposisi);
    if (count($_data_tanggapan) > 0) {
      $_tanggapan .= '<h3>Tanggapan</h3>';
      // query data tanggapan terkait disposisi ke database
      $_tanggapan .= '<table id="daftarTanggapan" class="table table-striped table-bordered table-condensed">';
      foreach ($this->dataTanggapanDisposisi($simbio, $_id_disposisi) as $_data_tanggapan) {
        $_tanggapan .= '<tr><th><span class="tanggapan-nama-unit">'.$_data_tanggapan['nama_unit'].'</span> pada <span class="tanggapan-tgl">'.$_data_tanggapan['tgl_dibuat'].'</span></th></tr>'."\n";
        $_tanggapan .= '<tr><td class="tanggapan-isi">'.$_data_tanggapan['tanggapan'].'</td></tr>'."\n";
      }
      $_tanggapan .= '</table>';
    } else {
      $_tanggapan .= '<div class="alert alert-error">Belum ada tanggapan untuk disposisi ini</div>'."\n";
    }

    $_output .= $_tanggapan;

    $simbio->loadView($_output, 'Detail Disposisi');
  }


    /**
     * Generate email disposisi
     * untuk digunakan pada saat pengiriman e-mail
     *
     * @param   object      $simbio
     * @param   int         $int_id_disposisi
     * @return  array
     */
    private function buatEmailDisposisi(&$simbio, $int_id_disposisi) {
        // ambil data disposisi
        $_q = $simbio->dbQuery('SELECT *
            FROM {disposisi} AS d LEFT JOIN {surat} AS s ON d.id_surat=s.id_surat WHERE d.id_disposisi=%d', $int_id_disposisi);
        $_d = $_q->fetch_assoc();

        // setting pesan e-mail
        $_url_konfirmasi = $this->global['base_url_https'].'/index.php?p=registrasi/konfirmasi/'.urldecode($_d['kode_konfirmasi']);

        $_pesan = '<p>Yth.<br />'."\n";
        $_pesan .= $_d['nama_pendaftar'].'</p>'."\n";
        $_pesan .= '<p></p>'."\n";
        $_pesan .= '<p>Anda menerima e-mail ini karena telah melakukan pendaftaran CPNS Online Kementerian Pendidikan dan Kebudayaan.<p>&nbsp;</p>'."\n";
        $_pesan .= 'ID Login adalah: '.$_d['id_login'].' - Gunakan ID Login ini untuk login melengkapi data pendaftaran Anda<p>&nbsp;</p>'."\n";
        $_pesan .= 'Untuk mengkonfirmasi dan melengkapi pendaftaran Anda, harap klik pada tautan/link berikut ini:'."\n";
        $_pesan .= '<p><a href="'.$_url_konfirmasi.'">Konfirmasi Pendaftaran CPNS Online</a>, atau arahkan browser anda ke alamat: '.$_url_konfirmasi.'</p>'."\n";
        $_pesan .= '</p>'."\n";

        $_return_data = array( 'email' => $_d['email'], 'nama' => $_d['nama_pendaftar'],
            'id_login' => $_d['id_login'], 'pesan' => $_pesan,
            'kode' => $_d['kode_konfirmasi'] );
        return $_return_data;
    }


  /**
   * Kirim e-mail disposisi
   *
   * @param   object      $simbio: Simbio framework object
   * @param   int         $int_id_disposisi: ID dari disposisi
   * @return  void
   */
  private function kirimEmailDisposisi(&$simbio, $int_id_disposisi) {
      if (!User::isUserLogin()) { return; }
      // load php mailer
      $simbio->loadLibrary('phpmailer', 'libraries/phpmailer/class.phpmailer.php');
      // ambil data pendaftar
      $_d = $this->buatEmailDisposisi($simbio, $int_id_disposisi);

      // setting pesan e-mail
      $_mail = new PHPMailer();
      $_mail->IsSMTP();
      $_mail->SMTPAuth = $this->global['mail']['auth_enable'];
      $_mail->Host = $this->global['mail']['server'];
      $_mail->Port = $this->global['mail']['server_port'];
      $_mail->Username = $this->global['mail']['auth_username'];
      $_mail->Password = $this->global['mail']['auth_password'];
      $_mail->SetFrom($this->global['mail']['from'], $this->global['mail']['from_name']);
      $_mail->AddReplyTo($this->global['mail']['reply_to'], $this->global['mail']['reply_to_name']);
      $_mail->SetFrom('cpns-online@kemdikbud.go.id', 'CPNS Online Kemdikbud');
      $_mail->AddAddress($_d['email'], $_d['nama']);
      $_mail->Subject = "Konfirmasi Pendaftaran CPNS Online Kementerian Pendidikan dan Kebudayaan";
      $_mail->AltBody = strip_tags($_d['pesan']);
      $_mail->MsgHTML($_d['pesan']);

      // kirim e-mail
      $_terkirim = $_mail->Send();

      // status
      if(!$_terkirim) {
        $simbio->addError('PENDAFTAR_EMAIL_SENT_FAILED', 'Maaf, E-mail gagal dikirimkan dengan pesan error sebagai berikut: '.$_mail->ErrorInfo);
        return false;
      } else {
        return true;
      }
  }

  private function kirimEmailPimpinan(&$simbio, $int_id_surat) {
      if (!User::isUserLogin()) { return; }
      // load data surat
      $_args = explode('/', $int_id_surat);
      $_q = $simbio->dbQuery('SELECT *
        FROM {surat_masuk} AS s WHERE s.id_surat=%d', $_args[0]);
      $_s = $_q->fetch_assoc();

      // load php mailer
      $simbio->loadLibrary('phpmailer', 'libraries/phpmailer/class.phpmailer.php');
      // ambil data pendaftar
      $_pesan = "Surat masuk No. <b>".$_s['no_surat']."</b>, tertanggal <b>".$_s['tgl_surat']."</b>, dari <b>".$_s['pengirim']."</b>, perihal ".$_s['perihal']." siap dibuatkan disposisi.";
      $_d = array( 'email' => isset($_args[1])?$_args[1]:$this->global['unit_head_email'], 'nama' => 'KaSubDit Pengadaan Tanah',
            'id_login' => "Sekretaris", 'pesan' => $_pesan,
            'kode' => "Hash code" );

      // setting pesan e-mail
      $_mail = new PHPMailer();
      $_mail->IsSMTP();
      $_mail->SMTPAuth = $this->global['mail']['auth_enable'];
	    $_mail->SMTPSecure = 'ssl';
      $_mail->Host = $this->global['mail']['server'];
      $_mail->Port = $this->global['mail']['server_port'];
      $_mail->Username = $this->global['mail']['auth_username'];
      $_mail->Password = $this->global['mail']['auth_password'];
      //$_mail->SetFrom($this->global['mail']['from'], $this->global['mail']['from_name']);
      $_mail->AddReplyTo($this->global['mail']['reply_to'], $this->global['mail']['reply_to_name']);
      $_mail->SetFrom($this->global['unit_secretary_email'], 'Sekretaris KaSubDit Pengadaan Tanah');
      $_mail->AddAddress($_d['email'], $_d['nama']);
      $_mail->Subject = "Surat masuk siap disposisi";
      $_mail->AltBody = strip_tags($_d['pesan']);
      $_mail->MsgHTML($_d['pesan']);

      // kirim e-mail
      $_terkirim = $_mail->Send();

      // status
      if(!$_terkirim) {
        $simbio->addError('PIMPINAN_EMAIL_SENT_FAILED', 'Maaf, E-mail gagal dikirimkan dengan pesan error sebagai berikut: '.$_mail->ErrorInfo);
        return false;
      } else {
        return true;
      }
  }

  private function kirimEmailUnit(&$simbio, $int_id_disposisi) {
      if (!User::isUserLogin()) { return; }
      // load data surat
      $_q = $simbio->dbQuery('SELECT d.perintah, s.perihal, s.pengirim, s.no_surat, s.tgl_surat
        FROM {surat_masuk} AS s
        LEFT JOIN {disposisi} as d ON d.id_surat = s.id_surat WHERE d.id_disposisi=%d', $int_id_disposisi);
      $_s = $_q->fetch_assoc();
      $_pesan = "Disposisi Pimpinan untuk Anda terkait Surat No. <b>".$_s['no_surat']."</b>, tertanggal <b>".$_s['tgl_surat']."</b>, dari <b>".$_s['pengirim']."</b>, perihal '".$_s['perihal']."' dengan perintah: '<quote>". $_s['perintah']. "</quote>' telah dibuat. Mohon untuk ditindaklanjuti.";

      // load php mailer
      $simbio->loadLibrary('phpmailer', 'libraries/phpmailer/class.phpmailer.php');
      $_d = array( 'email' => $this->global['unit_head_email'], 'nama' => 'KaSubDit Pengadaan Tanah',
        'id_login' => "KaSubDit Pengadaan Tanah", 'pesan' => $_pesan,
        'kode' => "Hash code" );

      // setting pesan e-mail
      $_mail = new PHPMailer();
      $_mail->IsSMTP();
      $_mail->SMTPAuth = $this->global['mail']['auth_enable'];
	    $_mail->SMTPSecure = 'ssl';
      $_mail->Host = $this->global['mail']['server'];
      $_mail->Port = $this->global['mail']['server_port'];
      $_mail->Username = $this->global['mail']['auth_username'];
      $_mail->Password = $this->global['mail']['auth_password'];
      //$_mail->SetFrom($this->global['mail']['from'], $this->global['mail']['from_name']);
      $_mail->AddReplyTo($this->global['mail']['reply_to'], $this->global['mail']['reply_to_name']);
      $_mail->SetFrom($this->global['unit_head_email'], 'KaSubDit Pengadaan Tanah');

      $_q = $simbio->dbQuery('SELECT duk.*, uk.email, uk.kepala
        FROM {disposisi_unit_kerja} AS duk
        LEFT JOIN {unit_kerja} as uk ON duk.id_unit = uk.id_unit WHERE duk.id_disposisi=%d', $int_id_disposisi);
      while ($_s = $_q->fetch_assoc()) {;
        $_mail->AddAddress($_s['email'], $_s['kepala']);
      }

      $_mail->Subject = "Disposisi surat masuk untuk diperhatikan";
      $_mail->AltBody = strip_tags($_d['pesan']);
      $_mail->MsgHTML($_d['pesan']);

      // kirim e-mail
      $_terkirim = $_mail->Send();

      // status
      if(!$_terkirim) {
        $simbio->addError('UNITKERJA_EMAIL_SENT_FAILED', 'Maaf, E-mail gagal dikirimkan dengan pesan error sebagai berikut: '.$_mail->ErrorInfo);
        return false;
      } else {
        return true;
      }
  }

  private function kirimEmailStaf(&$simbio, $int_id_disposisi) {
      if (!User::isUserLogin()) { return; }
      // load data surat

      $_args = explode('/', $int_id_disposisi);
      $_attach = array();
      $_q = $simbio->dbQuery('SELECT d.perintah, s.perihal, s.pengirim, s.no_surat, s.tgl_surat, f.namafile
        FROM {surat_masuk} AS s
        LEFT JOIN {disposisi} as d ON d.id_surat = s.id_surat
        LEFT JOIN {surat_file} as sf ON s.id_surat = sf.id_surat
        LEFT JOIN {file_surat} as f ON f.id_file = sf.id_file
        WHERE d.id_disposisi=%d', $_args[0]);
      while ($_s = $_q->fetch_assoc()) {
		$_pesan = "Disposisi Pimpinan untuk Anda terkait Surat No. <b>".$_s['no_surat']."</b>, tertanggal <b>".$_s['tgl_surat']."</b>, dari <b>".$_s['pengirim']."</b>, perihal '".$_s['perihal']."' dengan perintah: '<quote>". $_s['perintah']. "</quote>' telah dibuat.";
		$_attach[] = $_s['namafile'];
	  }

      // load php mailer
      $simbio->loadLibrary('phpmailer', 'libraries/phpmailer/class.phpmailer.php');
      $_d = array( 'email' => $this->global['unit_head_email'], 'nama' => 'KaSubDit Pengadaan Tanah',
            'id_login' => "KaSubDit Pengadaan Tanah", 'pesan' => $_pesan,
            'kode' => "Hash code" );

      // setting pesan e-mail
      $_mail = new PHPMailer();
      $_mail->IsSMTP();
      $_mail->SMTPDebug = 1;
      $_mail->SMTPAuth = $this->global['mail']['auth_enable'];
	    $_mail->SMTPSecure = 'ssl';
      $_mail->Host = $this->global['mail']['server'];
      $_mail->Port = $this->global['mail']['server_port'];
      $_mail->Username = $this->global['mail']['auth_username'];
      $_mail->Password = $this->global['mail']['auth_password'];
      $_mail->SetFrom($this->global['mail']['from'], $this->global['mail']['from_name']);
      $_mail->AddReplyTo($this->global['mail']['reply_to'], $this->global['mail']['reply_to_name']);
      $_mail->SetFrom('sids@subdittanah.net', 'SIDS auto mailer');

	  for ($i=1; $i<count($_args); ++$i) {
        $_mail->AddAddress($_args[$i], "");
      }

	  if (count($_attach)>0) {
		  foreach($_attach as $_file_item ) {
			if ($_file_item <> "") {
				$_mail->AddAttachment("files/surat/".$_file_item);
				//$lampiran .= "files/surat/".$_file_item;
			}
		  }
	  }

      $_mail->Subject = "Disposisi surat masuk untuk diperhatikan";
      $_mail->AltBody = strip_tags($_d['pesan']);
      $_mail->MsgHTML($_d['pesan']);

      // kirim e-mail
      $_terkirim = $_mail->Send();

      // status
      if(!$_terkirim) {
        $simbio->addError('STAF_EMAIL_SENT_FAILED', 'Maaf, E-mail gagal dikirimkan dengan pesan error sebagai berikut: '.$_mail->ErrorInfo);
        return false;
      } else {
        return true;
      }
  }

  private function kirimEmailSekretaris(&$simbio, $int_id_surat) {
      if (!User::isUserLogin()) { return; }
      // load data surat
      $_q = $simbio->dbQuery('SELECT *
        FROM {surat_masuk} AS s WHERE s.id_surat=%d', $int_id_surat);
      $_s = $_q->fetch_assoc();

      // load php mailer
      $simbio->loadLibrary('phpmailer', 'libraries/phpmailer/class.phpmailer.php');
      // ambil data pendaftar
      $_pesan = "Harap siapkan lembar disposisi untuk ditandatangani terkait: <br />Surat masuk No. <b>".$_s['no_surat']."</b>, tertanggal <b>".$_s['tgl_surat']."</b>, dari <b>".$_s['pengirim']."</b>, perihal '".$_s['perihal'].".'";
      $_d = array( 'email' => $this->global['unit_head_email'], 'nama' => 'Sekretaris KaSubDit',
        'id_login' => "Sekretaris", 'pesan' => $_pesan,
        'kode' => "Hash code" );

      // setting pesan e-mail
      $_mail = new PHPMailer();
      $_mail->IsSMTP();
      // $_mail->SMTPDebug = 1;
      $_mail->SMTPAuth = $this->global['mail']['auth_enable'];
	    $_mail->SMTPSecure = 'ssl';
      $_mail->Host = $this->global['mail']['server'];
      $_mail->Port = $this->global['mail']['server_port'];
      $_mail->Username = $this->global['mail']['auth_username'];
      $_mail->Password = $this->global['mail']['auth_password'];
      $_mail->SetFrom($this->global['unit_head_email'], 'Ka SubDir Pengadaan Tanah');
      $_mail->AddReplyTo($this->global['mail']['reply_to'], $this->global['mail']['reply_to_name']);
      $_mail->SetFrom($this->global['unit_head_email'], 'KaSubDit Pengadaan Tanah');
      $_mail->AddAddress($this->global['unit_secretary_email'], 'Sekretaris KaSubDit Pengadaan Tanah');
      $_mail->Subject = "Harap siapkan lembar disposisi";
      $_mail->AltBody = strip_tags($_d['pesan']);
      $_mail->MsgHTML($_d['pesan']);

      // kirim e-mail
      $_terkirim = $_mail->Send();

      // status
      if(!$_terkirim) {
        $simbio->addError('CETAK_EMAIL_SENT_FAILED', 'Maaf, E-mail gagal dikirimkan dengan pesan error sebagai berikut: '.$_mail->ErrorInfo);
        return false;
      } else {
        return true;
      }
  }

  /**
   * Hapus file beserta dengan datanya di database
   *
   */
  public function hapusFileUpload($simbio) {
    if (!User::isUserLogin('hapus file surat')) {
      // User::login($simbio, $str_args);
      return false;
    }
    $_file_ID = (integer)$_POST['fileid'];
    if (isset($_SESSION['uploaded'][$_file_ID])) {
      unset($_SESSION['uploaded'][$_file_ID]);
    }
    // ambil data file dari database
    $_file_q = $simbio->dbQuery('SELECT namafile FROM {file_surat} WHERE id_file=%d', $_file_ID);
    $_file_d = $_file_q->fetch_assoc();
    $_delete = @unlink('./files/surat/'.$_file_d['namafile']);
    if ($_delete) {
      $simbio->dbQuery('DELETE FROM {file_surat} WHERE id_file=%d', $_file_d);
      $simbio->dbQuery('DELETE FROM {surat_file} WHERE id_file=%d', $_file_d);
    }
    die();
  }

  public function hapus(&$simbio, $str_args) {
    if (!User::isUserLogin('hapus surat')) {
      $simbio->addError('SURAT_DELETE_ERROR', 'Maaf, anda tidak memiliki hak untuk menghapus data!');
      return false;
    }
    if (isset($_POST['record'])) {
      // convert scalar var to array var
      if (!is_array($_POST['record'])) {
          $_POST['record'][0] = $_POST['record'];
      }
      foreach ($_POST['record'] as $_rec_ID) {
          $_rec_ID = (integer)$_rec_ID;
          $simbio->dbDelete("id_surat='$_rec_ID'", 'surat_masuk');
          $simbio->dbDelete("id_surat='$_rec_ID'", 'disposisi');
          $simbio->dbDelete("id_surat='$_rec_ID'", 'surat_file');
      }
    }
    $simbio->addInfo('SURAT_DELETE_SUCCESS', 'Data surat berhasil dihapus');
    $this->index($simbio, $str_args);
  }

  public function hapusDisposisi(&$simbio, $str_args) {
    if (!User::isUserLogin('hapus disposisi')) {
      $simbio->addError('DISPOSISI_DELETE_ERROR', 'Maaf, anda tidak memiliki hak untuk menghapus data!');
      return false;
    }
    if (isset($_POST['record'])) {
      // convert scalar var to array var
      if (!is_array($_POST['record'])) {
          $_POST['record'][0] = $_POST['record'];
      }
      foreach ($_POST['record'] as $_rec_ID) {
          $_rec_ID = (integer)$_rec_ID;
          $simbio->dbDelete("id_disposisi='$_rec_ID'", 'disposisi');
          $simbio->dbDelete("id_disposisi='$_rec_ID'", 'tanggapan');
      }
    }
    $simbio->addInfo('SURAT_DELETE_SUCCESS', 'Data disposisi berhasil dihapus');
    $this->index($simbio, $str_args);
  }

  public function hapusTanggapan(&$simbio, $str_args) {
    if (!User::isUserLogin('hapus tanggapan')) {
      $simbio->addError('TANGGAPAN_DELETE_ERROR', 'Maaf, anda tidak memiliki hak untuk menghapus data!');
      return false;
    }
  }

  /**
   * Default module page method
   * All module must have this method
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  string
   */
  public function index(&$simbio, $str_args) {
    if (!User::isUserLogin()) {
      User::login($simbio, $str_args);
      return false;
    }
    $index_content = '<p class="alert alert-info lead">Daftar Surat Masuk</p>';
    // tampilkan daftar surat
    if (User::isUserLogin('daftar surat')) {
      $index_content = $this->generateDatagrid($simbio, $str_args);
    }

    $simbio->loadView($index_content, 'SIDS');
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
    // add css
    $simbio->addCSS(MODULES_WEB_BASE.'Disposisi/disposisi.css');
    $simbio->addJS(MODULES_WEB_BASE.'Disposisi/disposisi.js');
  }

  /**
   * Get block content
   *
   * @param   string    $str_block_type: block type to get
   * @return  string
   */
  public static function getBlock($str_block_type = '') {

  }

  public function cetakDisposisi(&$simbio, $str_args) {

  }

  public function statistik(&$simbio, $str_args) {
	$_statistic = '';
	$_statistic .='<div class="alert alert-success"><h4>STATISTIK Sistem Informasi Disposisi Surat (per '.date('d M Y').')</h4><br />';
	$_statistic .='<div class="accordion" id="accordion2">';

	// jumlah surat masuk
	$_sql_sekretaris = 'SELECT *
		FROM sids_surat_masuk';
    $_result_q = $simbio->dbQuery($_sql_sekretaris);
    $_result_no = $_result_q->num_rows;
	$_statistic .='<div class="accordion-group"><div class="accordion-heading">
		<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#stat_one">
		<i class="icon-tag"></i>&nbsp;Jumlah surat masuk : <strong>'.$_result_no.'</strong></a></div>';
	$_statistic .='<div id="stat_one" class="accordion-body collapse">
		<div class="accordion-inner"><ul>';
	while ($_result_d = $_result_q->fetch_assoc()) {
		$_statistic .= '<li>Tgl '.$_result_d['tgl_surat'].' dari '.$_result_d['pengirim']. ' tentang '.$_result_d['perihal'].'</li>';
	}
	$_statistic .='</ul></div></div></div>';
	// jumlah disposisi yg belum ditanggapi pimpinan
	$_sql_sekretaris = 'SELECT sm.*
		FROM sids_surat_masuk  as sm left join sids_disposisi as d ON sm.id_surat = d.id_surat
		WHERE d.perintah is NULL';
    $_result_q = $simbio->dbQuery($_sql_sekretaris);
    $_result_no = $_result_q->num_rows;
	$_statistic .='<div class="accordion-group"><div class="accordion-heading">
		<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#stat_two">
		<i class="icon-tag"></i>&nbsp;Jumlah surat masuk belum dilengkapi disposisi : <strong>'.$_result_no.'</strong></a></div>';
	$_statistic .='<div id="stat_two" class="accordion-body collapse">
		<div class="accordion-inner"><ul>';
	while ($_result_d = $_result_q->fetch_assoc()) {
		$_statistic .= '<li>Tgl '.$_result_d['tgl_surat'].' dari '.$_result_d['pengirim']. ' tentang '.$_result_d['perihal'].'</li>';
	}
	$_statistic .='</ul></div></div></div>';

	// jumlah disposisi yg harus di cetak
	$_sql_sekretaris = 'SELECT COUNT(sm.id_surat)
		FROM sids_surat_masuk  as sm left join sids_disposisi as d ON sm.id_surat = d.id_surat
		WHERE d.perintah <> ""';
    $_result_q = $simbio->dbQuery($_sql_sekretaris);
    $_result_d = $_result_q->fetch_array();
	$_statistic .='<div class="accordion-group"><div class="accordion-heading">
		<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#stat_three">
		<i class="icon-tag"></i>&nbsp;Jumlah disposisi yang BELUM DICETAK: <strong>'.$_result_d[0].'</strong></a></div>';
	$_statistic .='<div id="stat_three" class="accordion-body collapse">
		<div class="accordion-inner">
		&nbsp;
		</div></div></div>';

	// jumlah disposisi yg belum ditanggapi
	$_sql_sekretaris = 'SELECT distinct sm.id_surat
		FROM sids_surat_masuk as sm
		LEFT JOIN sids_disposisi as d ON sm.id_surat = d.id_surat
		LEFT JOIN sids_tanggapan_unit as t ON t.id_disposisi = d.id_disposisi';
    $_result_q = $simbio->dbQuery($_sql_sekretaris);
    $_result_d = $_result_q->num_rows;
	$_statistic .='<div class="accordion-group"><div class="accordion-heading">
		<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#stat_four">
		<i class="icon-tag"></i>&nbsp;Jumlah disposisi yang BELUM DITANGGAPI: <strong>'.$_result_d.'</strong></a></div>';
	$_statistic .='<div id="stat_four" class="accordion-body collapse">
		<div class="accordion-inner">
		&nbsp;
		</div></div></div>';

    $_statistic .='</div>';
    $simbio->loadView($_statistic, 'SIDS');

  }

  /**
   * Form pencarian data surat
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function pencarian(&$simbio, $str_args) {
    if (!User::isUserLogin('daftar surat')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      User::login($simbio, $str_args);
      return false;
    }
    // ID
    $_updateID = (integer)trim($str_args);
    $_update_q = $simbio->dbQuery('SELECT * FROM {surat_masuk} WHERE id_surat=%d', $_updateID);
    $_update_d = $_update_q->fetch_assoc();

    $simbio->addInfo('CARI_SURAT_INFO', 'Masukkan kata kunci pada satu atau lebih ruas pada form di bawah ini
      untuk menemukan dokumen yang anda butuhkan');
    // set token
    $_SESSION['token'] = Utility::generateRandomString(20);

    // Form data entry surat
    $_form = new FormOutput('surat', $this->global['base_url'].'/index.php?p=disposisi', 'post');
    $_form->submitName = 'cari';
    $_form->submitValue = __('Cari');
    // define form elements
    $_form_items[] = array('id' => 'no_surat', 'label' => __('No Surat'), 'type' => 'text', 'size' => '100',
      'description' => 'Nomor surat masuk', 'class' => 'input-xxlarge');
    $_form_items[] = array('id' => 'no_disposisi', 'label' => __('Kode Disposisi'), 'type' => 'text', 'size' => '100',
      'description' => 'Kode disposisi', 'class' => 'input-xxlarge');
    $_form_items[] = array('id' => 'perihal', 'label' => __('Perihal surat'), 'type' => 'text', 'size' => '255',
      'description' => 'Perihal surat masuk', 'class' => 'input-xxlarge');
    $_form_items[] = array('id' => 'pengirim', 'label' => __('Nama pengirim'), 'type' => 'text', 'size' => '100',
      'description' => 'Nama lengkap pengirim surat', 'class' => 'input-xxlarge');
    $_form_items[] = array('id' => 'kepada', 'label' => __('Kepada Yth.'), 'type' => 'text', 'size' => '100',
      'description' => 'Tujuan surat', 'class' => 'input-xxlarge');

    $_form_items[] = array('id' => 'tgl_surat', 'label' => __('Tanggal surat'), 'type' => 'date',
      'description' => 'Tanggal surat', 'class' => 'input-small');
    $_form_items[] = array('id' => 'tgl_terima', 'label' => __('Tanggal terima'), 'type' => 'date',
      'description' => 'Tanggal surat diterima', 'class' => 'input-small');

    // set form token
    $_form_items[] = array('id' => 'advance', 'type' => 'hidden', 'value' => 'advance search');
    $_form_items[] = array('id' => 'tkn', 'type' => 'hidden', 'value' => $_SESSION['token']);
    foreach ($_form_items as $_item) {
      $_form->add($_item);
    }

    $_form_output = $_form->buildTable();

    // load main content again
    $simbio->loadView($_form_output, 'Form Cari Surat');
  }

  /**
   * Form insert/update surat masuk
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function update(&$simbio, $str_args) {
    if (!User::isUserLogin('tambah surat')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    // ID
    $_updateID = (integer)trim($str_args);
    $_update_q = $simbio->dbQuery('SELECT * FROM {surat_masuk} WHERE id_surat=%d', $_updateID);
    $_update_d = $_update_q->fetch_assoc();

    $simbio->addInfo('SURAT_BARU_INFO', 'Masukkan data-data surat masuk pada form di bawah ini');
    // set token
    $_SESSION['token'] = Utility::generateRandomString(20);

    // Form data entry surat
    $_form = new FormOutput('surat', $this->global['base_url'].'/index.php?p=disposisi/simpan', 'post');
    $_form->submitName = 'simpan';
    $_form->submitValue = __('Simpan');
    // define form elements
    $_form_items[] = array('id' => 'no_surat', 'label' => __('No Surat'), 'type' => 'text', 'size' => '100',
      'description' => 'Nomor surat masuk', 'class' => 'input-xxlarge', 'value' => $_update_d['no_surat'],
      'required' => 1);
    $_form_items[] = array('id' => 'perihal', 'label' => __('Perihal surat'), 'type' => 'textarea', 'size' => '255',
      'description' => 'Perihal surat masuk', 'class' => 'input-xxlarge', 'value' => $_update_d['perihal'],
      'required' => 1);
    $_form_items[] = array('id' => 'pengirim', 'label' => __('Nama pengirim'), 'type' => 'text', 'size' => '100',
      'description' => 'Nama lengkap pengirim surat', 'class' => 'input-xxlarge', 'value' => $_update_d['pengirim'],
      'required' => 1);
    $_form_items[] = array('id' => 'kepada', 'label' => __('Kepada Yth.'), 'type' => 'text', 'size' => '100',
      'description' => 'Tujuan surat', 'class' => 'input-xxlarge', 'value' => $_update_d['kepada']);

    $_file_surat_upload_element = FormMaker::createFormElement(array('id' => 'file_surat', 'label' => __('File surat'), 'type' => 'file'));
    $_file_surat_content = $_file_surat_upload_element->out();
    $_file_surat_content .= ' <a class="btn ajaxUpload" href="./index.php?p=Disposisi/uploadfilesurat/'.$_updateID.'"><i class="icon-arrow-up"></i> Upload</a>';
    $_file_surat_content .= ' <small>Maksimum ukuran file yang di-upload <strong>'.(  round($this->global['upload']['max_size']/(1024*1024), 2) ).' MB</strong></small>';
    // daftar file yang sudah di-upload
    $_file_surat_content .= '<table id="daftarFile" class="table table-striped table-bordered table-condensed">';
    if (isset($_SESSION['uploaded']) && count($_SESSION['uploaded']) > 0) {
      foreach ($_SESSION['uploaded'] as $_uploaded_file) {
        $_file_surat_content .= '<tr id="file_'.$_uploaded_file[0].'"><td>'.$_uploaded_file[1].'</td><td>'.( $_uploaded_file[2] ).' MB</td><td><a class="btn btn-mini btn-danger hapusFile" fileid="'.$_uploaded_file[0].'" href="./index.php?p=Disposisi/hapusfileupload">Hapus</a></td></tr>';
      }
    } else if ($_updateID) {
      // query data file terkait surat ke database
      $_file_surat_q = $simbio->dbQuery('SELECT * FROM {surat_file} AS sf
        LEFT JOIN {file_surat} AS f ON sf.id_file=f.id_file
        WHERE sf.id_surat=%d', $_updateID);
      while ($_data_surat = $_file_surat_q->fetch_assoc()) {
        $_file_surat_content .= '<tr id="file_'.$_data_surat['id_file'].'"><td>'.$_data_surat['namafile'].'</td><td>'.( round($_data_surat['file_size']/(1024*1024), 2) ).' MB</td><td><a class="btn btn-mini btn-danger hapusFile" target="uploadTarget" href="./index.php?p=Disposisi/hapusfileupload" fileid="'.$_data_surat['id_file'].'">Hapus</a></td></tr>';
      }
    }
    $_file_surat_content .= '</table>';

    $_file_surat_content .= ' <iframe name="uploadTarget" id="uploadTarget" class="hidden_iframe"></iframe>';
    $_form_items[] = array('id' => 'file_surat', 'label' => __('Upload file surat'), 'type' => 'content',
      'content' => $_file_surat_content, 'description' => 'File soft copy dari surat',
      'required' => 1);

    $_form_items[] = array('id' => 'tgl_surat', 'label' => __('Tanggal surat'), 'type' => 'date',
      'description' => 'Tanggal surat', 'class' => 'input-small', 'value' => $_update_d['tgl_surat']?$_update_d['tgl_surat']:date('Y-m-d'),
      'required' => 1);
    $_form_items[] = array('id' => 'tgl_terima', 'label' => __('Tanggal terima'), 'type' => 'date',
      'description' => 'Tanggal surat diterima', 'class' => 'input-small', 'value' => $_update_d['tgl_terima']?$_update_d['tgl_terima']:date('Y-m-d'),
      'required' => 1);
    /*
    $_form_items[] = array('id' => 'tgl_proses', 'label' => __('Tanggal proses'), 'type' => 'date',
      'description' => 'Tanggal surat diproses', 'class' => 'input-small', 'value' => $_update_d['tgl_proses']?$_update_d['tgl_proses']:date('Y-m-d'),
      'required' => 1);
    */
    $_form_items[] = array('id' => 'email_pimpinan', 'label' => __('E-mail pimpinan'), 'type' => 'text',
      'description' => 'Alamat e-mail pimpinan', 'class' => 'input-small', 'value' => $this->global['unit_head_email'],
      'required' => 1);
    $_form_items[] = array('id' => 'hp_pimpinan', 'label' => __('Ponsel/HP Pimpinan'), 'type' => 'text',
      'description' => 'Nomor ponsel atau HP pimpinan', 'class' => 'input-small', 'value' => $this->global['unit_head_cell'],
      'required' => 1);

    if ($_updateID > 0) {
     $_form_items[] = array('id' => 'update', 'type' => 'hidden', 'value' => $_updateID);
    }

    // set form token
    $_form_items[] = array('id' => 'tkn', 'type' => 'hidden', 'value' => $_SESSION['token']);
    foreach ($_form_items as $_item) {
      $_form->add($_item);
    }

    $_form_output = $_form->buildTable();

    // load main content again
    $simbio->loadView($_form_output, 'Form Update Surat');
  }


  /**
   * Form insert/update data disposisi
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function updateDisposisi(&$simbio, $str_args) {
    if (!User::isUserLogin('disposisi surat')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    $_output = '';
    // ID Surat
    if (preg_match('@^surat@i', $str_args)) {
      $_id_disposisi = 0;
      $_id_surat = str_ireplace('surat/', '', $str_args);
      $_disposisi_q = $simbio->dbQuery('SELECT d.*, s.* FROM {surat_masuk} AS s
        LEFT JOIN {disposisi} AS d ON s.id_surat=d.id_surat
        WHERE s.id_surat=%d', $_id_surat);
    } else {
      $_id_disposisi = (integer)trim($str_args);
      $_disposisi_q = $simbio->dbQuery('SELECT d.*, s.* FROM {surat_masuk} AS s
        INNER JOIN {disposisi} AS d ON s.id_surat=d.id_surat
        WHERE d.id_disposisi=%d', $_id_disposisi);
    }
    $_disposisi_d = $_disposisi_q->fetch_assoc();
    $_id_surat = $_disposisi_d['id_surat'];
    if ($_disposisi_d['id_disposisi']) {
      $_id_disposisi = $_disposisi_d['id_disposisi'];
    }

    if ($_disposisi_d['perihal'] && $_disposisi_d['pengirim']) {
      $simbio->addInfo('DISPOSISI_BARU_INFO', sprintf('Anda akan melakukan disposisi untuk surat dengan perihal <strong class="perihal">%s</strong>
        yang dikirim oleh <strong class="pengirim">%s</strong>', $_disposisi_d['perihal'], $_disposisi_d['pengirim']));
    } else {
      $simbio->addInfo('DISPOSISI_UPDATE_INFO', sprintf('Anda akan mengubah data disposisi dengan nomor <strong class="perihal">%s</strong>', $_disposisi_d['no_disposisi']));
    }

    // set token
    $_SESSION['token'] = Utility::generateRandomString(20);

    // Form data entry disposisi
    $_form = new FormOutput('disposisi', $this->global['base_url'].'/index.php?p=disposisi/simpandisposisi', 'post');
    $_form->submitName = 'tambahBaru';
    $_form->submitValue = __('Simpan Disposisi');
    // define form elements
    $_form_items[] = array('id' => 'no_surat', 'label' => __('Nomor Surat'), 'type' => 'content', 'content' => $_disposisi_d['no_surat']);

    // query data file terkait surat ke database
    $_file_surat = '<table id="daftarFile" class="table table-striped table-bordered table-condensed">';
    foreach ($this->dataFileSurat($simbio, $_id_surat) as $_data_surat) {
      $_file_surat .= '<tr><td>'.$_data_surat['namafile'].'</td><td>'.( round($_data_surat['file_size']/(1024*1024), 2) ).' MB</td><td><a class="btn btn-mini btn-info" href="./files/surat/'.$_data_surat['namafile'].'">Baca/Lihat</a></td></tr>';
    }
    $_file_surat .= '</table>';

    $_form_items[] = array('id' => 'file_surat', 'label' => __('File surat'), 'type' => 'content', 'content' => $_file_surat);

    $_form_items[] = array('id' => 'no_disposisi', 'label' => __('Kode Disposisi'), 'type' => 'text', 'size' => '45',
      'class' => 'input-xlarge', 'value' => $_disposisi_d['no_disposisi']?$_disposisi_d['no_disposisi']:$_disposisi_d['no_disposisi'],
      'required' => 1);
    $_form_items[] = array('id' => 'perintah', 'label' => __('Perintah'), 'type' => 'textarea', 'size' => '10000',
      'description' => 'Perintah untuk ditindaklanjuti', 'class' => 'input-xxlarge', 'value' => $_disposisi_d['perintah'],
      'required' => 1);
    $_form_items[] = array('id' => 'status', 'label' => __('Status disposisi'), 'type' => 'dropdown',
      'description' => 'Status disposisi', 'options' => Master::getMasterData($simbio, 'status'), 'value' => $_disposisi_d['status'],
      'required' => 1);

    $_unit_kerja = array();
    if ($_id_disposisi) {
    $_unit_kerja_disposisi_data = $this->dataUnitKerjaDisposisi($simbio, $_id_disposisi);
    foreach ($_unit_kerja_disposisi_data as $uk) {
      $_unit_kerja[] = $uk['id_unit'];
    }
    }
    $_form_items[] = array('id' => 'unit_kerja', 'label' => __('Unit kerja disposisi'), 'type' => 'checkbox',
      'description' => 'Unit kerja yang menerima disposisi', 'options' => Master::getMasterData($simbio, 'unit_kerja'), 'value' => $_unit_kerja,
      'required' => 1);
    $_form_items[] = array('id' => 'email_penerima_disposisi', 'label' => __('E-mail penerima disposisi'), 'type' => 'text', 'size' => '45',
      'description' => 'Alamat e-mail lain yang akan menerima e-mail berisi disposisi. Pisahkan antar alamat e-mail dengan tanda koma.', 'class' => 'input-xlarge',
      'value' => $_disposisi_d['email_penerima_disposisi']);
    $_form_items[] = array('id' => 'tgl_disposisi', 'label' => __('Tanggal disposisi'), 'type' => 'date',
      'description' => 'Tanggal disposisi', 'class' => 'input-small', 'value' => $_disposisi_d['tgl_disposisi']?$_disposisi_d['tgl_disposisi']:date('Y-m-d'),
      'required' => 1);
    /**
    $_form_items[] = array('id' => 'file_surat', 'label' => __('Upload file revisi disposisi'), 'type' => 'file',
      'description' => 'File surat hasil revisi disposisi', 'attr' => array('disabled' => 'disabled'),
      'required' => 0);
    **/
    // ID surat
    $_form_items[] = array('id' => 'surat', 'type' => 'hidden', 'value' => $_id_surat);

    if ($_id_disposisi > 0) {
     $_form_items[] = array('id' => 'update', 'type' => 'hidden', 'value' => $_id_disposisi);
    }

    // set form token
    $_form_items[] = array('id' => 'tkn', 'type' => 'hidden', 'value' => $_SESSION['token']);
    foreach ($_form_items as $_item) {
      $_form->add($_item);
    }

    $_form_output = $_form->buildTable();

    $_output .= $_form_output;

    // load main content again
    $simbio->loadView($_output, 'Update Disposisi');
  }


  /**
   * Form insert/update data disposisi
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function updateKodeDisposisi(&$simbio, $str_args) {
    if (!User::isUserLogin('disposisi kode surat')) {
      // User::login($simbio, $str_args);
      return false;
    }
    $_output = '';
    $_ids = explode('/', $str_args);
    // ID Surat
    $_id_surat = (integer)trim($_ids[0]);
    $_surat_q = $simbio->dbQuery('SELECT * FROM {surat_masuk} WHERE id_surat=%d', $_id_surat);
    $_surat_d = $_surat_q->fetch_assoc();
    $_id_disposisi = 0;

    if ($_surat_d['perihal'] && $_surat_d['pengirim']) {
      $simbio->addInfo('DISPOSISI_BARU_INFO', sprintf('Anda akan memberikan kode disposisi untuk surat dengan perihal <strong class="perihal">%s</strong>
        yang dikirim oleh <strong class="pengirim">%s</strong>', $_surat_d['perihal'], $_surat_d['pengirim']));
    }

    // set token
    $_SESSION['token'] = Utility::generateRandomString(20);

    // Form data entry disposisi
    $_form = new FormOutput('disposisi', $this->global['base_url'].'/index.php?p=disposisi/simpandisposisi', 'post');
    $_form->submitName = 'tambahBaru';
    $_form->submitValue = __('Simpan Disposisi');
    // define form elements
    $_form_items[] = array('id' => 'no_surat', 'label' => __('Nomor'), 'type' => 'text', 'size' => '45',
      'class' => 'input-xlarge', 'value' => $_surat_d['no_surat'], 'attr' => array('readonly' => 'readonly'),
      'required' => 1);
    $_form_items[] = array('id' => 'no_disposisi', 'label' => __('Kode Disposisi'), 'type' => 'text', 'size' => '45',
      'class' => 'input-xlarge', 'required' => 1);
    // ID surat
    $_form_items[] = array('id' => 'surat', 'type' => 'hidden', 'value' => $_id_surat);
    $_form_items[] = array('id' => 'updateKodeDisposisi', 'type' => 'hidden', 'value' => 1);

    // set form token
    $_form_items[] = array('id' => 'tkn', 'type' => 'hidden', 'value' => $_SESSION['token']);
    foreach ($_form_items as $_item) {
      $_form->add($_item);
    }

    $_form_output = $_form->build();

    $_output .= $_form_output;

    // load main content again
    $simbio->loadView($_output, 'Update Kode Disposisi');
  }


  /**
   * Form insert/update data tanggapan disposisi
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  private function updateTanggapan(&$simbio, $str_args) {
    if (!User::isUserLogin('beri tanggapan')) {
      // User::login($simbio, $str_args);
      return false;
    }
    $_output = '';
    $_ids = explode('/', $str_args);
    // ID Disposisi
    $_id_disposisi = (integer)trim($_ids[0]);
    $_disposisi_q = $simbio->dbQuery('SELECT * FROM {disposisi} WHERE id_disposisi=%d', $_id_disposisi);
    $_disposisi_d = $_disposisi_q->fetch_assoc();
    // ID tanggapan
    if (isset($_ids[1])) {
    $_id_tanggapan = (integer)trim($_ids[1]);
    } else {
    $_id_tanggapan = 0;
    }
    $_tanggapan_q = $simbio->dbQuery('SELECT tgp.*, uk.* FROM {tanggapan_unit} AS tgp
    LEFT JOIN {unit_kerja} AS uk ON tgp.id_unit=uk.id_unit
    WHERE id_tanggapan=%d', $_id_tanggapan);
    $_tanggapan_d = $_tanggapan_q->fetch_assoc();

    if ($_disposisi_d['no_disposisi'] && $_disposisi_d['perintah']) {
      $simbio->addInfo('TANGGAPAN_BARU_INFO', sprintf('Anda akan memberikan tanggapan untuk disposisi nomor <strong>%s</strong>', $_disposisi_d['no_disposisi']));
    } else {
      $simbio->addInfo('TANGGAPAN_UPDATE_INFO', sprintf('Anda akan mengubah data tanggapan berikut'));
    }

    // set token
    $_SESSION['token'] = Utility::generateRandomString(20);

    // Form data entry disposisi
    $_form = new FormOutput('tanggapandisposisi', $this->global['base_url'].'/index.php?p=disposisi/simpantanggapan', 'post');
    $_form->submitName = 'tambahBaru';
    $_form->submitValue = __('Simpan Tanggapan');
    // define form elements
    $_form_items[] = array('id' => 'tanggapan', 'label' => __('Tanggapan'), 'type' => 'textarea', 'size' => '10000',
      'description' => 'Tanggapan terhadap disposisi', 'class' => 'input-xxlarge', 'value' => $_tanggapan_d['tanggapan']);


    $_staf_unit_kerja = array();
    $_staf_unit_kerja_data = $this->dataStafUnitKerjaDisposisi($simbio, isset($_SESSION['User']['UnitKerja'])?$_SESSION['User']['UnitKerja']:1);
    foreach ($_staf_unit_kerja_data as $_staf) {
      $_staf_unit_kerja[] = array($_staf['email'], $_staf['nama']);
    }

    $_form_items[] = array('id' => 'staf', 'label' => __('Staf unit kerja'), 'type' => 'checkbox',
      'description' => 'Staf unit kerja yang ditunjuk untuk menindaklanjuti',
      'options' => $_staf_unit_kerja,
      'value' => '');


    // ID Disposisi
    if ($_id_disposisi > 0) {
    $_form_items[] = array('id' => 'disposisi', 'type' => 'hidden', 'value' => $_id_disposisi);
    } else if ($_tanggapan_d['id_disposisi'] > 0) {
    $_form_items[] = array('id' => 'disposisi', 'type' => 'hidden', 'value' => $_tanggapan_d['id_disposisi']);
    }

    if ($_id_tanggapan > 0) {
     $_form_items[] = array('id' => 'update', 'type' => 'hidden', 'value' => $_id_tanggapan);
    }

    // set form token
    $_form_items[] = array('id' => 'tkn', 'type' => 'hidden', 'value' => $_SESSION['token']);
    foreach ($_form_items as $_item) {
    $_form->add($_item);
    }

    $_form_output = $_form->build();

    $_output .= $_form_output;

    return $_output;
    // load main content again
    // $simbio->loadView($_output, 'Update Tanggapan Disposisi');
  }

  /**
   * Upload file surat
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $id_surat: ID surat terkait
   * @return  void
   */
  public function uploadFileSurat($simbio, $id_surat = 0) {
    if (!User::isUserLogin('upload file surat')) {
      return false;
    }

    if (!$_FILES['file_surat']['size']) {
      die();
    }

    $_datetime = date('Y-m-d H:i:s');
    $_upload_dir = './files/surat/';
    $_upload_filename = basename($_FILES['file_surat']['name']);

    $_data['namafile'] = $simbio->filterizeSQLString($_upload_filename, true);
    $_data['mime_type'] = $_FILES['file_surat']['type'];
    $_data['judul'] = $_upload_filename;
    $_data['file_size'] = $_FILES['file_surat']['size'];
    $_data['tgl_upload'] = $_datetime;
    $_filesize_mb = round($_FILES['file_surat']['size']/(1024*1024), 2);
    // ambil extension file
    $_file_ext = substr($_FILES['file_surat']['name'], strrpos($_FILES['file_surat']['name'], '.'));

    ob_start();
    if ($_FILES['file_surat']['size'] > $this->global['upload']['max_size'] || !in_array($_file_ext, $this->global['upload']['allowed'])) {
      echo 'Besar ukuran file yang di-upload terlalu besar atau tipe file tidak diizinkan. Pastikan ukuran file yang anda upload tidak lebih dari ';
      echo ( $_filesize_mb ).' MB (mega bytes) dan dalam format ('.implode(',', $this->global['upload']['allowed']).')';
    }

    // upload
    $_upload = move_uploaded_file($_FILES['file_surat']['tmp_name'], $_upload_dir.$_upload_filename);
    $_upload_error = ob_get_clean();

    if ($_upload) {
      // masukkan data file ke database
      $_insert = $simbio->dbInsert($_data, 'file_surat', $last_insert_ID);
      if (!$_insert) {
        $simbio->writeLogs('Disposisi', 'File surat '.$_upload_filename.' gagal di-upload karena suatu hal', 'SURAT_UPLOAD_ERROR');
      } else {
        $simbio->writeLogs('Disposisi', 'File surat '.$_upload_filename.' berhasil di-upload', 'SURAT_UPLOAD_SUCCESS');
      }
      $_SESSION['uploaded'][$last_insert_ID] = array($last_insert_ID, $_upload_filename, $_filesize_mb);

      echo '<!DOCTYPE html>';
      echo '<html><body>';
      echo '<script type="text/javascript">';
      echo 'top.alert("File '.$_upload_filename.' berhasil di-upload");';
      echo 'top.jQuery("#daftarFile").append("<tr id=\"file_'.$last_insert_ID.'\"><td>'.$_upload_filename.'</td><td>'.( $_filesize_mb ).' MB</td><td><a class=\"btn btn-mini btn-danger hapusFile\" fileid=\"'.$last_insert_ID.'\" href=\"./index.php?p=Disposisi/hapusfilesurat\">Hapus</a></td></tr>");';
      echo '</script>';
      echo '</body></html>';
    } else {
      die($_upload_error);
    }

    die();
  }

  /**
   * Menyimpan data ke database
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function simpan(&$simbio, $str_args) {
    if (!User::isUserLogin('tambah surat')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    // update data surat masuk
    $_datetime = date('Y-m-d H:i:s');
    $_data['no_surat'] = $simbio->filterizeSQLString($_POST['no_surat'], true);
    $_data['perihal'] = $simbio->filterizeSQLString($_POST['perihal'], true);
    $_data['pengirim'] = $simbio->filterizeSQLString($_POST['pengirim'], true);
    $_data['kepada'] = $simbio->filterizeSQLString($_POST['kepada'], true);
    $_data['tgl_surat'] = $simbio->filterizeSQLString($_POST['tgl_surat'], true);
    $_data['tgl_terima'] = $simbio->filterizeSQLString($_POST['tgl_terima'], true);
    // $_data['tgl_proses'] = $simbio->filterizeSQLString($_POST['tgl_proses'], true);
    $_data['last_update'] = $_datetime;

    // update data ke database
    $_update = false;
    $_ID = 0;
    if (isset($_POST['update'])) {
    $_ID = $_POST['update'];
    $_update = $simbio->dbUpdate($_data, 'surat_masuk', sprintf('id_surat=%d', $_POST['update']));
    if (!$_update) {
      $simbio->addError('SURAT_UPDATE_ERROR', 'Maaf, data surat gagal dimutakhirkan ke database, karena terjadinya galat/error pada sistem!');
      $simbio->writeLogs('Disposisi', 'Data surat gagal dimutakhirkan karena suatu hal', 'SURAT_UPDATE_ERROR');
    } else {
      $simbio->addInfo('SURAT_UPDATE_SUCCESS', 'Data surat berhasil dimutakhirkan');
      $simbio->writeLogs('Disposisi', 'Data surat berhasil dimutakhirkan', 'SURAT_UPDATE_SUCCESS');
    }
    } else {
    $_data['input_date'] = $_datetime;
    $_update = $simbio->dbInsert($_data, 'surat_masuk', $_last_insert_ID);
    $_ID = $_last_insert_ID;
    if (!$_update) {
      $simbio->addError('SURAT_INSERT_ERROR', 'Maaf, data surat gagal dimasukkan ke database, karena terjadinya galat/error pada sistem!');
      $simbio->writeLogs('Disposisi', 'Data surat gagal dimutakhirkan karena suatu hal', 'SURAT_INSERT_ERROR');
    } else {
      $simbio->addInfo('SURAT_INSERT_SUCCESS', 'Data surat berhasil dimasukkan ke dalam database');
      $simbio->writeLogs('Disposisi', 'Data surat berhasil dimasukkan ke dalam database', 'SURAT_INSERT_SUCCESS');
      $_emailPimpinan = $this->kirimEmailPimpinan(&$simbio, $_ID);
    }
    }

    // masukkan data relasi file upload
    if (isset($_SESSION['uploaded']) && count($_SESSION['uploaded']) > 0 && $_update) {
    foreach ($_SESSION['uploaded'] as $_uploaded_file) {
      $_file_data = array();
      $_datetime = date('Y-m-d');
      $_file_data['id_surat'] = (integer)$_ID;
      $_file_data['id_file'] = (integer)$_uploaded_file[0];
      $_file_data['tgl_dibuat'] = $_datetime;
      $_file_data['tgl_diubah'] = $_datetime;
      $_file_data['akses'] = '1';
      $_update_file = $simbio->dbInsert($_file_data, 'surat_file');
    }
    }

    // bersihkan token
    unset($_SESSION['token']);
    unset($_SESSION['uploaded']);
    $this->updateKodeDisposisi($simbio, $_ID);
    // $this->index($simbio, $str_args);
  }


  /**
   * Menyimpan data ke database
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function simpanDisposisi(&$simbio, $str_args) {
    if (!(User::isUserLogin('disposisi kode surat') || User::isUserLogin('disposisi surat'))) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    // update data disposisi
    $_datetime = date('Y-m-d H:i:s');
    $_data['no_disposisi'] = $simbio->filterizeSQLString($_POST['no_disposisi'], true);
    $_data['id_surat'] = (integer)$_POST['surat'];
    $_data['hash_code'] = utility::generateRandomString(20);
    if (!isset($_POST['updateKodeDisposisi'])) {
      $_data['perintah'] = $simbio->filterizeSQLString($_POST['perintah'], true);
      $_data['status'] = $simbio->filterizeSQLString($_POST['status'], true);
      $_data['tgl_disposisi'] = $simbio->filterizeSQLString($_POST['tgl_disposisi'], true);
      $_data['tgl_diubah'] = $_datetime;
    }

    // update data ke database
    $_update = false;
    $_ID = 0;
    // update data ke database
    if (isset($_POST['update'])) {
      $_ID = $_POST['update'];
      $_update = $simbio->dbUpdate($_data, 'disposisi', sprintf('id_disposisi=%d', $_POST['update']));
      if (!$_update) {
        $simbio->addError('DISPOSISI_UPDATE_ERROR', 'Maaf, data disposisi gagal dimutakhirkan ke database, karena terjadinya galat/error pada sistem!');
        $simbio->writeLogs('Disposisi', 'Data disposisi gagal dimutakhirkan karena suatu hal', 'DISPOSISI_UPDATE_ERROR');
      } else {
        $simbio->addInfo('DISPOSISI_UPDATE_SUCCESS', 'Data disposisi berhasil dimutakhirkan');
        $simbio->writeLogs('Disposisi', 'Data disposisi berhasil dimutakhirkan', 'DISPOSISI_UPDATE_SUCCESS');
        // set flag sudah di disposisi pada data surat
        if (!isset($_POST['updateKodeDisposisi'])) {
          $simbio->dbQuery('UPDATE {surat_masuk} SET disposisi=1 WHERE id_surat=%d', $_data['id_surat']);
        }
      }
    } else {
      $_data['tgl_dibuat'] = $_datetime;
      $_insert = $simbio->dbInsert($_data, 'disposisi', $_ID);
      if (!$_insert) {
        $simbio->addError('DISPOSISI_INSERT_ERROR', 'Maaf, data disposisi gagal dimasukkan ke database, karena terjadinya galat/error pada sistem!');
        $simbio->writeLogs('Disposisi', 'Data disposisi gagal dimutakhirkan karena suatu hal', 'DISPOSISI_INSERT_ERROR');
      } else {
        $simbio->addInfo('DISPOSISI_INSERT_SUCCESS', 'Data disposisi berhasil dimasukkan ke dalam database');
        $simbio->writeLogs('Disposisi', 'Data disposisi berhasil dimasukkan ke dalam database', 'DISPOSISI_INSERT_SUCCESS');
      }
    }
    // add data unit kerja yang menerima disposisi
    if (isset($_POST['unit_kerja'])) {
      if (is_array($_POST['unit_kerja']) && count($_POST['unit_kerja']) > 0) {
        foreach ($_POST['unit_kerja'] as $uk) {
          $_uk_disposisi_data['id_disposisi'] = $_ID;
          $_uk_disposisi_data['id_unit'] = (integer)$uk;
          $_update_disposisi_unit_kerja = $simbio->dbInsert($_uk_disposisi_data, 'disposisi_unit_kerja');
        }
      }
    }

    if (isset($_POST['perintah']) AND $_POST['perintah'] <> "" AND $_update) {
		$_emailUnit = $this->kirimEmailUnit(&$simbio, $_ID);
		$_emailSekretaris = $this->kirimEmailSekretaris(&$simbio, $_data['id_surat']);
	}


    // bersihkan token
    unset($_SESSION['token']);
    if (isset($_POST['updateKodeDisposisi'])) {
      $this->index($simbio, $str_args);
    } else {
      $this->detailDisposisi($simbio, $_ID);
    }
  }


  /**
   * Menyimpan data ke database
   *
   * @param   object    $simbio: Simbio framework object
   * @param   string    $str_args: method main argument
   * @return  void
   */
  public function simpanTanggapan(&$simbio, $str_args) {
    if (!User::isUserLogin('beri tanggapan')) {
      $simbio->addError('NO_PRIVILEGES_ERROR', 'Anda tidak memiliki hak untuk masuk ke bagian ini');
      // User::login($simbio, $str_args);
      return false;
    }
    // update data tanggapan
    $_datetime = date('Y-m-d H:i:s');
    $_data['tanggapan'] = $simbio->filterizeSQLString($_POST['tanggapan'], true);
    $_data['id_unit'] = isset($_SESSION['UnitKerja'])?$_SESSION['UnitKerja']:'1';
    $_data['id_disposisi'] = (integer)$_POST['disposisi'];
    $_data['tgl_diubah'] = $_datetime;

    // update data ke database
    if (isset($_POST['update'])) {
      $_update = $simbio->dbUpdate($_data, 'tanggapan_unit', sprintf('id_tanggapan=%d', $_POST['update']));
      if (!$_update) {
        $simbio->addError('TANGGAPAN_UPDATE_ERROR', 'Maaf, data tanggapan gagal dimutakhirkan ke database, karena terjadinya galat/error pada sistem!');
        $simbio->writeLogs('Disposisi', 'Data tanggapan gagal dimutakhirkan karena suatu hal', 'TANGGAPAN_UPDATE_ERROR');
      } else {
        $simbio->addInfo('TANGGAPAN_UPDATE_SUCCESS', 'Data tanggapan berhasil dimutakhirkan');
        $simbio->writeLogs('Disposisi', 'Data tanggapan berhasil dimutakhirkan', 'TANGGAPAN_UPDATE_SUCCESS');
        // set flag sudah di tanggapi pada data disposisi
        $simbio->dbQuery('UPDATE {disposisi} SET tanggapan=1 WHERE id_disposisi=%d', $_data['id_disposisi']);
      }
    } else {
      $_data['tgl_dibuat'] = $_datetime;
      if ($_data['tanggapan'] <> "") {
		  $_insert = $simbio->dbInsert($_data, 'tanggapan_unit');
		  if (!$_insert) {
			$simbio->addError('TANGGAPAN_INSERT_ERROR', 'Maaf, data tanggapan gagal dimasukkan ke database, karena terjadinya galat/error pada sistem!');
			$simbio->writeLogs('Disposisi', 'Data tanggapan gagal dimutakhirkan karena suatu hal', 'TANGGAPAN_INSERT_ERROR');
		  } else {
			$simbio->addInfo('TANGGAPAN_INSERT_SUCCESS', 'Data tanggapan berhasil dimasukkan ke dalam database');
			$simbio->writeLogs('Disposisi', 'Data tanggapan berhasil dimasukkan ke dalam database', 'TANGGAPAN_INSERT_SUCCESS');
			// set flag sudah di tanggapi pada data disposisi
			$simbio->dbQuery('UPDATE {disposisi} SET tanggapan=1 WHERE id_disposisi=%d', $_data['id_disposisi']);
		  }
	  } else if (isset($_POST['staf']) AND count($_POST['staf'])>0) {
		  $_delegasi = $_data['id_disposisi'];
		  $_delegasi .= '/'.implode("/",$_POST['staf']);
		  $_emailStaf = $this->kirimEmailStaf(&$simbio, $_delegasi);
		  //echo $_emailStaf;
	  }

    }

    // bersihkan token
    unset($_SESSION['token']);
    $this->detailDisposisi($simbio, $_data['id_disposisi']);
  }


  /**
   * Ubah format tanggal ke format d-m-Y
   *
   * @param   string    $str_tanggal
   * @return  string
   */
  public static function ubahFormatTanggal($str_tanggal) {
    return date('d-m-Y', strtotime($str_tanggal));
  }

  /**
   * Validasi data pendaftar
   *
   * @param   array     $arr_data
   * @param   string    $str_error = pesan error yang diberikan
   * @return  boolean
   */
  private function validasiData($arr_data, &$str_error) {
    return true;
  }
}
