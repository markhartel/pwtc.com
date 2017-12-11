<?php

class PwtcMileage {

	const VIEW_MILEAGE_CAP = 'pwtc_view_mileage';
	const EDIT_MILEAGE_CAP = 'pwtc_edit_mileage';
	const EDIT_RIDERS_CAP = 'pwtc_edit_riders';
	const DB_OPS_CAP = 'pwtc_mileage_db_ops';

	const MEMBER_SYNC_ACT = 'Synchronize';
	const RIDE_MERGE_ACT = 'Consolidate';
	const DB_RESTORE_ACT = 'Restore';
	const RIDER_PURGE_ACT = 'Purge';

    private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	// Initializes plugin WordPress hooks.
	private static function init_hooks() {
		self::$initiated = true;

		add_action( 'template_redirect', 
			array( 'PwtcMileage', 'download_riderid' ) );

		// Register script and style enqueue callbacks
		add_action( 'wp_enqueue_scripts', 
			array( 'PwtcMileage', 'load_report_scripts' ) );

		// Register shortcode callbacks
		add_shortcode('pwtc_rider_report', 
			array( 'PwtcMileage', 'shortcode_rider_report'));
/*
		add_shortcode('pwtc_achievement_last_year', 
			array( 'PwtcMileage', 'shortcode_ly_lt_achvmnt'));
*/
		add_shortcode('pwtc_mileage_year_to_date', 
			array( 'PwtcMileage', 'shortcode_ytd_miles'));
		add_shortcode('pwtc_mileage_last_year', 
			array( 'PwtcMileage', 'shortcode_ly_miles'));
		add_shortcode('pwtc_mileage_lifetime', 
			array( 'PwtcMileage', 'shortcode_lt_miles'));
		add_shortcode('pwtc_rides_led_year_to_date', 
			array( 'PwtcMileage', 'shortcode_ytd_led'));
		add_shortcode('pwtc_rides_led_last_year', 
			array( 'PwtcMileage', 'shortcode_ly_led'));
		add_shortcode('pwtc_rides_year_to_date', 
			array( 'PwtcMileage', 'shortcode_ytd_rides'));
		add_shortcode('pwtc_rides_last_year', 
			array( 'PwtcMileage', 'shortcode_ly_rides'));
		add_shortcode('pwtc_led_rides_year_to_date', 
			array( 'PwtcMileage', 'shortcode_ytd_led_rides'));
		add_shortcode('pwtc_led_rides_last_year', 
			array( 'PwtcMileage', 'shortcode_ly_led_rides'));
		add_shortcode('pwtc_posted_rides_wo_sheets', 
			array( 'PwtcMileage', 'shortcode_rides_wo_sheets'));
		add_shortcode('pwtc_riderid_download', 
			array( 'PwtcMileage', 'shortcode_riderid_download'));
/*
		add_shortcode('pwtc_ridecal_download', 
			array( 'PwtcMileage', 'shortcode_ridecal_download'));
*/

		// Register background action task callbacks 
		add_action( 'pwtc_mileage_consolidation', 
			array( 'PwtcMileage', 'consolidation_callback') );  
		add_action( 'pwtc_mileage_member_sync', 
			array( 'PwtcMileage', 'member_sync_callback') );  
		add_action( 'pwtc_mileage_purge_nonriders', 
			array( 'PwtcMileage', 'purge_nonriders_callback') );  
		add_action( 'pwtc_mileage_cvs_restore', 
			array( 'PwtcMileage', 'cvs_restore_callback') );  
		add_action( 'pwtc_mileage_updmembs_load', 
			array( 'PwtcMileage', 'updmembs_load_callback2') );  
	}

	public static function download_riderid() {
		if (isset($_POST['download_riderid']) and
			isset($_POST['rider_name']) and
			isset($_POST['rider_id']) and
			isset($_POST['expire_date'])) {
			header('Content-Description: File Transfer');
			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename=rider_card.pdf");
			require('fpdf.php');	
			$pdf = new FPDF();
			$pdf->AddPage();
			$pdf->Rect(0, 0, 85, 55);
			$pdf->Image(PWTC_MILEAGE__PLUGIN_DIR . 'pwtc_logo.png', 2, 10, 20, 20);
			$pdf->SetFont('Arial', '', 20);
			$pdf->Text(2, 40, 'PWTC');
			$pdf->SetXY(23, 15);
			$pdf->SetFont('Arial', 'I', 18);
			$pdf->Cell(60, 10, $_POST['rider_name'], 0, 0,'C');
			$pdf->SetFont('Arial', '', 14);
			$pdf->Text(50, 34, $_POST['rider_id']);
			$pdf->Text(59, 50, $_POST['expire_date']);
			$pdf->SetFont('Arial', '', 5);
			$pdf->Text(50, 38, 'MEMBER ID');
			$pdf->Text(59, 54, 'EXPIRES');
			//$pdf->Output();
			$pdf->Output('F', 'php://output');
			die;
		}
	}

	/*************************************************************/
	/* Script and style enqueue callback functions
	/*************************************************************/

	public static function load_report_scripts() {
        wp_enqueue_style('pwtc_mileage_report_css', 
			PWTC_MILEAGE__PLUGIN_URL . 'reports-style.css' );
	}

	/*************************************************************/
	/* Background action task callbacks
	/*************************************************************/

	public static function consolidation_callback() {
		PwtcMileage_DB::job_set_status(self::RIDE_MERGE_ACT, PwtcMileage_DB::STARTED_STATUS);

		$thisyear = date('Y', current_time('timestamp'));
		$yearbeforelast = intval($thisyear) - 2;
		$title = '[Totals Through ' . $yearbeforelast . ']';
		$maxdate = '' . $yearbeforelast . '-12-31';

		$num_rides = PwtcMileage_DB::get_num_rides_before_date($maxdate);	
		if ($num_rides == 0) {
			PwtcMileage_DB::job_set_status(self::RIDE_MERGE_ACT, PwtcMileage_DB::FAILED_STATUS, 
				'no ridesheets were found for ' . $yearbeforelast);
		}
		else if ($num_rides == 1) {
			PwtcMileage_DB::job_set_status(self::RIDE_MERGE_ACT, PwtcMileage_DB::FAILED_STATUS, 
				'' . $yearbeforelast . ' ridesheets are already consolidated');
		}
		else {
			$status = PwtcMileage_DB::insert_ride($title, $maxdate);
			if (false === $status or 0 === $status) {
				PwtcMileage_DB::job_set_status(self::RIDE_MERGE_ACT,PwtcMileage_DB::FAILED_STATUS, 'could not insert new ridesheet, mileage database may be corrupted. Contact administrator.');
			}
			else {
				$rideid = PwtcMileage_DB::get_new_ride_id();
				if (isset($rideid) and is_int($rideid)) {
					$status = PwtcMileage_DB::rollup_ridesheets($rideid, $maxdate);
					if (isset($status['error'])) {
						PwtcMileage_DB::job_set_status(self::RIDE_MERGE_ACT,PwtcMileage_DB::FAILED_STATUS, $status['error'] . ', mileage database may be corrupted. Contact administrator.');
					}
					else {
						PwtcMileage_DB::job_set_status(self::RIDE_MERGE_ACT, PwtcMileage_DB::SUCCESS_STATUS, 
							$status['m_inserts'] . ' mileages inserted, ' . 
							$status['m_deletes'] . ' mileages deleted, ' . 
							$status['l_inserts'] . ' leaders inserted, ' . 
							$status['l_deletes'] . ' leaders deleted, ' . 
							'1 ridesheet inserted, ' . 
							$status['r_deletes'] . ' ridesheets deleted');
					}
				}
				else {
					PwtcMileage_DB::job_set_status(self::RIDE_MERGE_ACT, PwtcMileage_DB::FAILED_STATUS, 'new ridesheet ID is invalid, mileage database may be corrupted. Contact administrator.');
				}
			}
		}	
	}

	public static function member_sync_callback() {
		PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::STARTED_STATUS);
		$members = pwtc_mileage_fetch_membership();
		if (count($members) == 0) {
			PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 'no members in membership list');
		}
		else {
			$results = self::update_membership_list($members);
			if ($results['insert_fail'] > 0) {
				PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 
					$results['insert_fail'] . ' failed updates, ' . 
					$results['validate_fail'] . ' failed validations, ' . 
					$results['insert_succeed'] . ' members inserted, ' .
					$results['update_succeed'] . ' members updated, ' .
					$results['duplicate_record'] . ' duplicates found');
			}
			else if ($results['validate_fail'] > 0) {
				PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 
					$results['validate_fail'] . ' failed validations, ' .
					$results['insert_succeed'] . ' members inserted, ' .
					$results['update_succeed'] . ' members updated, ' .
					$results['duplicate_record'] . ' duplicates found');
			}
			else {
				PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::SUCCESS_STATUS, 
					$results['insert_succeed'] . ' members inserted, ' . 
					$results['update_succeed'] . ' members updated, ' .
					$results['duplicate_record'] . ' duplicates found');
			}	
		}
	}

	public static function purge_nonriders_callback() {
		PwtcMileage_DB::job_set_status(self::RIDER_PURGE_ACT, PwtcMileage_DB::STARTED_STATUS);
		$status = PwtcMileage_DB::delete_all_nonriders();
		if (false === $status or 0 === $status) {
			PwtcMileage_DB::job_set_status(self::RIDER_PURGE_ACT, PwtcMileage_DB::FAILED_STATUS, 'database delete failed');
		}
		else {
			PwtcMileage_DB::job_set_status(self::RIDER_PURGE_ACT, PwtcMileage_DB::SUCCESS_STATUS, 
				$status . ' riders deleted');
		}
	}

	public static function updmembs_load_callback() {
		PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::STARTED_STATUS);
		$upload_dir = wp_upload_dir();
		$plugin_upload_dir = $upload_dir['basedir'] . '/pwtc_mileage';
		$members_file = $plugin_upload_dir . '/updmembs.dbf';
		if (!file_exists($members_file)) {
			PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 'updmembs.dbf file does not exist');
		}
		else {
			include('dbf_class.php');
			try {			
				$dbf = new dbf_class($members_file);
				if (self::validate_updmembs_file($dbf)) {
					$results = self::process_updmembs_file($dbf);
					if ($results['insert_fail'] > 0) {
						PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 
							$results['insert_fail'] . 'failed updates, ' . 
							$results['validate_fail'] . ' failed validations, ' . 
							$results['insert_succeed'] . ' members inserted, ' .
							$results['update_succeed'] . ' members updated, ' .
							$results['duplicate_record'] . ' duplicates found');
					}
					else if ($results['validate_fail'] > 0) {
						PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 
							$results['validate_fail'] . ' failed validations, ' .
							$results['insert_succeed'] . ' members inserted, ' .
							$results['update_succeed'] . ' members updated, ' .
							$results['duplicate_record'] . ' duplicates found');
					}
					else {
						PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::SUCCESS_STATUS, 
							$results['insert_succeed'] . ' members inserted, ' . 
							$results['update_succeed'] . ' members updated, ' .
							$results['duplicate_record'] . ' duplicates found');
					}	
				}
				else {
					PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 'invalid dbf file contents');
				}
			} 
			catch (Exception $e) {
				pwtc_mileage_write_log('Exception thrown from dbf_class: ' . $e->getMessage());
				PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 'invalid dbf file');
			}
			unlink($members_file);
		}
	}

	public static function updmembs_load_callback2() {
		PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::STARTED_STATUS);
		$upload_dir = wp_upload_dir();
		$plugin_upload_dir = $upload_dir['basedir'] . '/pwtc_mileage';
		$members_file = $plugin_upload_dir . '/updmembs.dbf';
		$members_csv = $plugin_upload_dir . '/updmembs.csv';
		$plugin_upload_url = $upload_dir['baseurl'] . '/pwtc_mileage';
		$members_url = $plugin_upload_url . '/updmembs.csv';
		if (!file_exists($members_file)) {
			PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 'updmembs.dbf file does not exist');
		}
		else {
			include('dbf_class.php');
			try {			
				$dbf = new dbf_class($members_file);
				if (self::validate_updmembs_file($dbf)) {
					$results = self::process_updmembs_file2($dbf);
					$fh = fopen($members_csv, 'w');
					self::write_export_csv_file($fh, $results['data']);
					fclose($fh);					
					$status = PwtcMileage_DB::load_members_for_update($members_url);
					if ($results['id_val_fail'] > 0 or $results['lname_val_fail'] > 0 or
						$results['fname_val_fail'] > 0 or $results['expir_val_fail'] > 0) {
						PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, 
							PwtcMileage_DB::SUCCESS_STATUS, 
							'updmembs file loaded, ' . $results['id_val_fail'] . ' invalid IDs, ' . 
							$results['fname_val_fail'] . ' invalid first names, ' .
							$results['lname_val_fail'] . ' invalid last names, ' .
							$results['expir_val_fail'] . ' invalid expiration dates');	
					}
					else {
						PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, 
							PwtcMileage_DB::SUCCESS_STATUS, 
							'updmembs file loaded, no validation errors');	
					}
					unlink($members_csv);
				}
				else {
					PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 'invalid dbf file contents');
				}
			} 
			catch (Exception $e) {
				pwtc_mileage_write_log('Exception thrown from dbf_class: ' . $e->getMessage());
				PwtcMileage_DB::job_set_status(self::MEMBER_SYNC_ACT, PwtcMileage_DB::FAILED_STATUS, 'invalid dbf file');
			}
			unlink($members_file);
		}
	}

	public static function cvs_restore_callback() {
		PwtcMileage_DB::job_set_status(self::DB_RESTORE_ACT, PwtcMileage_DB::STARTED_STATUS);
		$upload_dir = wp_upload_dir();
		$plugin_upload_dir = $upload_dir['basedir'] . '/pwtc_mileage';
		$members_file = $plugin_upload_dir . '/' . PwtcMileage_DB::MEMBER_TABLE . '.csv';
		$rides_file = $plugin_upload_dir . '/' . PwtcMileage_DB::RIDE_TABLE . '.csv';
		$mileage_file = $plugin_upload_dir . '/' . PwtcMileage_DB::MILEAGE_TABLE . '.csv';
		$leaders_file = $plugin_upload_dir . '/' . PwtcMileage_DB::LEADER_TABLE . '.csv';
		if (!file_exists($members_file)) {
			PwtcMileage_DB::job_set_status(self::DB_RESTORE_ACT, PwtcMileage_DB::FAILED_STATUS, 'members upload file does not exist');
		}
		else if (!file_exists($rides_file)) {
			PwtcMileage_DB::job_set_status(self::DB_RESTORE_ACT, PwtcMileage_DB::FAILED_STATUS, 'rides upload file does not exist');
		}
		else if (!file_exists($mileage_file)) {
			PwtcMileage_DB::job_set_status(self::DB_RESTORE_ACT, PwtcMileage_DB::FAILED_STATUS, 'mileage upload file does not exist');
		}
		else if (!file_exists($leaders_file)) {
			PwtcMileage_DB::job_set_status(self::DB_RESTORE_ACT, PwtcMileage_DB::FAILED_STATUS, 'leaders upload file does not exist');
		}
		else {
			$plugin_upload_url = $upload_dir['baseurl'] . '/pwtc_mileage';
			$members_url = $plugin_upload_url . '/' . PwtcMileage_DB::MEMBER_TABLE . '.csv';
			$rides_url = $plugin_upload_url . '/' . PwtcMileage_DB::RIDE_TABLE . '.csv';
			$mileage_url = $plugin_upload_url . '/' . PwtcMileage_DB::MILEAGE_TABLE . '.csv';
			$leaders_url = $plugin_upload_url . '/' . PwtcMileage_DB::LEADER_TABLE . '.csv';

			$delete_l = PwtcMileage_DB::delete_leaders_for_restore();
			$delete_m = PwtcMileage_DB::delete_mileage_for_restore();
			$delete_r = PwtcMileage_DB::delete_rides_for_restore();
			$delete_p = PwtcMileage_DB::delete_members_for_restore();

			PwtcMileage_DB::load_members_for_restore($members_url);
			PwtcMileage_DB::load_rides_for_restore($rides_url);
			PwtcMileage_DB::load_mileage_for_restore($mileage_url);
			PwtcMileage_DB::load_leaders_for_restore($leaders_url);

			$load_p = PwtcMileage_DB::count_members();
			$load_r = PwtcMileage_DB::count_rides();
			$load_m = PwtcMileage_DB::count_mileage();
			$load_l = PwtcMileage_DB::count_leaders();

			unlink($members_file);
			unlink($rides_file);
			unlink($mileage_file);
			unlink($leaders_file);

			PwtcMileage_DB::job_set_status(self::DB_RESTORE_ACT, PwtcMileage_DB::SUCCESS_STATUS, 
				$delete_l . ' leaders deleted, ' . 
				$delete_m . ' mileages deleted, ' . 
				$delete_r . ' ridesheets deleted, ' . 
				$delete_p . ' members deleted, ' . 
				$load_p . ' members loaded, ' . 
				$load_r . ' ridesheets loaded, ' . 
				$load_m . ' mileages loaded, ' . 
				$load_l . ' leaders loaded');
		}	
	}

	/*************************************************************/
	/* Background action task utility functions.
	/*************************************************************/

	public static function validate_updmembs_file($dbf) {
		if ($dbf->dbf_num_field < 4) {
			return false;
		}
		if ($dbf->dbf_names[0]['type'] != 'C') {
			return false;
		}
		if ($dbf->dbf_names[1]['type'] != 'C') {
			return false;
		}
		if ($dbf->dbf_names[2]['type'] != 'C') {
			return false;
		}
		if ($dbf->dbf_names[3]['type'] != 'D') {
			return false;
		}
		return true;
	}

	public static function process_updmembs_file($dbf) {
		$val_fail_count = 0;
		$ins_fail_count = 0;
		$ins_succ_count = 0;
		$upd_succ_count = 0;
		$dup_rec_count = 0;
		$hashmap = self::create_member_hashmap();
    	$num_rec = $dbf->dbf_num_rec;
		for ($i=0; $i<$num_rec; $i++) {
			if ($row = $dbf->getRow($i)) {
				$memberid = trim($row[0]);
				$firstname = trim($row[1]);
				$lastname = trim($row[2]);
				$date = trim($row[3]);
				$expirdate = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
				$status = self::update_member_item($hashmap, $memberid, $firstname, $lastname, $expirdate);
				switch ($status) {
					case "val_fail":
						pwtc_mileage_write_log($row);
						$val_fail_count++;
						break;
					case "dup_rec":
						$dup_rec_count++;
						break;
					case "ins_fail":
						$ins_fail_count++;
						break;
					case "insert":
						$ins_succ_count++;
						break;
					case "update":
						$upd_succ_count++;
						break;
				}
			}
		}		
		return array('validate_fail' => $val_fail_count,
			'insert_fail' => $ins_fail_count,
			'insert_succeed' => $ins_succ_count,
			'update_succeed' => $upd_succ_count,
			'duplicate_record' => $dup_rec_count);
	}

	public static function process_updmembs_file2($dbf) {
		$id_val_fail = 0;
		$fname_val_fail = 0;
		$lname_val_fail = 0;
		$expir_val_fail = 0;
		$data = array();
    	$num_rec = $dbf->dbf_num_rec;
		for ($i=0; $i<$num_rec; $i++) {
			if ($row = $dbf->getRow($i)) {
				$memberid = trim($row[0]);
				$firstname = trim($row[1]);
				$lastname = trim($row[2]);
				$date = trim($row[3]);
				$expirdate = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
				$val_fail = false;
				if (!self::validate_member_id_str($memberid)) {
					$id_val_fail++;
					$val_fail = true;
				}
				else if (!self::validate_member_name_str($lastname)) {
					$lname_val_fail++;
					$val_fail = true;
				}
				else if (!self::validate_member_name_str($firstname)) {
					$fname_val_fail++;
					$val_fail = true;
				}
				else if (!self::validate_date_str($expirdate)) {
					$expir_val_fail++;
					$val_fail = true;
				}
				else {
					array_push($data, array($memberid, $firstname, $lastname, $expirdate));
				}
				if ($val_fail) {
					pwtc_mileage_write_log('Updmembs file record validation failure:');
					pwtc_mileage_write_log($row);
				}
			}	
		}		
		return array('id_val_fail' => $id_val_fail,
			'lname_val_fail' => $lname_val_fail,
			'fname_val_fail' => $fname_val_fail,
			'expir_val_fail' => $expir_val_fail,
			'data' => $data);
	}

	public static function write_export_csv_file($fp, $data, $header = null) {
		if ($header != null) {
			fputcsv($fp, $header);
		}
		foreach ($data as $item) {
    		fputcsv($fp, $item);
		}		
	}

	public static function create_member_hashmap() {
		$riders = PwtcMileage_DB::fetch_members_for_export();
		$hashmap = array();
		foreach ( $riders as $item ) {
			$hashmap[$item[0]] = $item;
		}
		return $hashmap;		
	}

	public static function update_member_item($hashmap, $memberid, $firstname, $lastname, $expirdate) {
		if (!self::validate_member_id_str($memberid)) {
			return 'val_fail';
		}
		else if (!self::validate_member_name_str($lastname)) {
			return 'val_fail';
		}
		else if (!self::validate_member_name_str($firstname)) {
			return 'val_fail';
		}
		else if (!self::validate_date_str($expirdate)) {
			return 'val_fail';
		}
		$result = 'insert';
		if (array_key_exists($memberid, $hashmap)) {
			$rider = $hashmap[$memberid];
			if ($firstname == $rider[1] and $lastname == $rider[2] and $expirdate == $rider[3]) {
				return 'dup_rec';
			}
			$result = 'update';
		}
		$status = PwtcMileage_DB::insert_rider($memberid, $lastname, $firstname, $expirdate);	
		if (false === $status or 0 === $status) {
			return 'ins_fail';
		}
		return $result;
	}

	public static function update_membership_list($members) {
		$val_fail_count = 0;
		$ins_fail_count = 0;
		$ins_succ_count = 0;
		$upd_succ_count = 0;
		$dup_rec_count = 0;
		$hashmap = self::create_member_hashmap();
		foreach ( $members as $item ) {
			$memberid = trim($item[0]);
			$firstname = trim($item[1]);
			$lastname = trim($item[2]);
			$expirdate = trim($item[3]);
			$status = self::update_member_item($hashmap, $memberid, $firstname, $lastname, $expirdate);
			switch ($status) {
				case "val_fail":
					$val_fail_count++;
					break;
				case "dup_rec":
					$dup_rec_count++;
					break;
				case "ins_fail":
					$ins_fail_count++;
					break;
				case "insert":
					$ins_succ_count++;
					break;
				case "update":
					$upd_succ_count++;
					break;
			}
		}
		return array('validate_fail' => $val_fail_count,
			'insert_fail' => $ins_fail_count,
			'insert_succeed' => $ins_succ_count,
			'update_succeed' => $upd_succ_count,
			'duplicate_record' => $dup_rec_count);
	}

	/*************************************************************/
	/* Shortcode report table utility functions.
	/*************************************************************/

	// Returns a rider's display name (first and last) given the rider ID.
	public static function get_rider_name($id) {
		$rider = PwtcMileage_DB::fetch_rider($id);
		$name = '';
		if (count($rider) > 0) {
			$r = $rider[0];
			$name = $r['first_name'] . ' ' . $r['last_name'];
		}
		else {
			$name = $id;
		}
		return $name;
	}

	// Generates the HTML for a shortcode report table.
	public static function shortcode_build_table($meta, $data, $atts, $content = null) {
		$plugin_options = self::get_plugin_options();
		$hide_id = true;
		if ($atts['show_id'] == 'on') {
			$hide_id = false;
		}
		$id = null;
		if ($meta['id_idx'] >= 0 and $atts['highlight_user'] == 'on') {
			try {
				$id = pwtc_mileage_get_member_id();
			}
			catch (Exception $e) {
			}
		}
		$out = '<div>';  
		$out .= '<table class="pwtc-mileage-rwd-table">';
		if (empty($content)) {
			if ($atts['caption'] == 'on') {
				$out .= '<caption>' . $meta['title'] . '</caption>';
			}
		}
		else {
			$out .= '<caption>' . do_shortcode($content) . '</caption>';
		}
		if (count($data) > 0) {
			$out .= '<tr>';
			$i = 0;
			foreach( $meta['header'] as $item ):
				if ($meta['id_idx'] === $i) {
					if (!$hide_id) {
						$out .= '<th>' . $item . '</th>';						
					}
				} 
				else {
					$out .= '<th>' . $item . '</th>';
				}
				$i++;
			endforeach;	
			$out .= '</tr>';
			foreach( $data as $row ):
				$outrow = '';
				$i = 0;
				$highlight = false;
				foreach( $row as $item ):
					$label = $meta['header'][$i];
					$lbl_attr = 'data-th="' . $label . '"';
					if ($meta['date_idx'] == $i) {
						$fmtdate = date('D M j Y', strtotime($item));
						$outrow .= '<td ' . $lbl_attr . '>' . $fmtdate . '</td>';
					}
					else if ($meta['id_idx'] === $i) {
						if ($id !== null and $id == $item) {
							$highlight = true;
						}
						if (!$hide_id) {
							$outrow .= '<td ' . $lbl_attr . '>' . $item . '</td>';						
						}
					}
					else {
						if (0 === strpos($item, 'http://') or 0 === strpos($item, 'https://')) {
							$outrow .= '<td ' . $lbl_attr . '><a href="' . $item . 
								'" target="_blank">View</a></td>';
						}
						else {
							$outrow .= '<td ' . $lbl_attr . '>' . $item . '</td>';
						}
					}
					$i++;
				endforeach;	
				if ($highlight) {
					$out .= '<tr class="highlight">' . $outrow . '</tr>';
				}
				else {
					$out .= '<tr>' . $outrow . '</tr>';
				}
			endforeach;
		}
		else {
			$out .= '<tr><td data-th="Data">No records found!</td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';
		return $out;
	}

	// Generates the default attribute object for a shortcode report table.
	public static function normalize_atts($atts) {
    	$a = shortcode_atts(array(
        		'show_id' => 'off',
       			'highlight_user' => 'on',
				'sort_by' => 'off',
				'sort_order' => 'asc',
				'minimum' => 1,
				'caption' => 'on'
			), $atts);
		return $a;
	}

	// Generates the SQL 'order by' clause from a shortcode mileage report table attribute object.
	public static function build_mileage_sort($atts) {
		$order = 'asc';
		if ($atts['sort_order'] == 'desc') {
			$order = 'desc';
		}
		$sort = 'mileage ' . $order;
		if ($atts['sort_by'] == 'name') {
			$sort = 'last_name ' . $order . ', first_name ' . $order;
		}
		return $sort;
	}

	// Generates the SQL 'order by' clause from a shortcode leader report table attribute object.
	public static function build_rides_led_sort($atts) {
		$order = 'asc';
		if ($atts['sort_order'] == 'desc') {
			$order = 'desc';
		}
		$sort = 'rides_led ' . $order;
		if ($atts['sort_by'] == 'name') {
			$sort = 'last_name ' . $order . ', first_name ' . $order;
		}
		return $sort;
	}

	// Gets the minimum value from a shortcode report table attribute object.
	public static function get_minimum_val($atts) {
		$min = 0;
		if ($atts['minimum'] > 0) {
			$min = $atts['minimum'];
		}
		return $min;
	}

	/*************************************************************/
	/* Shortcode report generation functions
	/*************************************************************/
 
	// Generates the [pwtc_rider_report] shortcode.
	public static function shortcode_rider_report($atts) {
    	$a = shortcode_atts(array('type' => 'both'), $atts);
		$out = '<div>';
		try {
			$id = pwtc_mileage_get_member_id();
			$result = PwtcMileage_DB::fetch_rider($id);
			if (count($result) > 0) {
				$out .= $result[0]['first_name'] . ' ' . $result[0]['last_name'] . 
					', your rider ID is ' . $id . '.'; 
			}
			else {
				$out .= 'Your rider ID is ' . $id . '.';
			}
			if ($a['type'] == 'mileage' or $a['type'] == 'both') {
				$out .= ' You have ridden <strong>';
				$out .= PwtcMileage_DB::get_ytd_rider_mileage($id);
				$out .= '</strong> miles with the club so far this year. Last year you rode <strong>';
				$out .= PwtcMileage_DB::get_ly_rider_mileage($id);
				$out .= '</strong> miles. Your total lifetime club mileage is <strong>';
				$out .= PwtcMileage_DB::get_lt_rider_mileage($id);
				$out .= '</strong> miles.';
			}
			if ($a['type'] == 'leader' or $a['type'] == 'both') {
				$out .= ' You have led <strong>';
				$out .= PwtcMileage_DB::get_ytd_rider_led($id);
				$out .= '</strong> club rides so far this year. Last year you led <strong>';
				$out .= PwtcMileage_DB::get_ly_rider_led($id);
				$out .= '</strong> rides.';
			}
		}
		catch (Exception $e) { 
			switch ($e->getMessage()) {
				case "notloggedin":
					$out .= 'Please log in to see your club rider report.';
					break;
				case "idnotfound":
					$out .= 'Cannot show club rider report, rider ID not found.';
					break;
				case "multidfound":
					$out .= 'Cannot show club rider report, multiple rider IDs found.';
					break;
				default:
					$out .= 'Cannot show club rider report, unknown error.';
			}
		}
		$out .= '</div>';
		return $out;
	}

	// Generates the [pwtc_achievement_last_year] shortcode.
/*
	public static function shortcode_ly_lt_achvmnt($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "<div>Please log in to see TBD</div>";
		}	
		$a = self::normalize_atts($atts);
		$sort = self::build_mileage_sort($a);
		$meta = PwtcMileage_DB::meta_ly_lt_achvmnt();
		$data = PwtcMileage_DB::fetch_ly_lt_achvmnt(ARRAY_N, $sort);
		$out = self::shortcode_build_table($meta, $data, $a, $content);
		return $out;
	}
*/

	// Generates the [pwtc_mileage_year_to_date] shortcode.
	public static function shortcode_ytd_miles($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "<div>Please log in to see the year-to-date mileage report.</div>";
		}	
		$a = self::normalize_atts($atts);
		$sort = self::build_mileage_sort($a);
		$min = self::get_minimum_val($a);
		$meta = PwtcMileage_DB::meta_ytd_miles();
		$data = PwtcMileage_DB::fetch_ytd_miles(ARRAY_N, $sort, $min);
		$out = self::shortcode_build_table($meta, $data, $a, $content);
		return $out;
	}

	// Generates the [pwtc_mileage_last_year] shortcode.
	public static function shortcode_ly_miles($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "<div>Please log in to see the last year mileage report.</div>";
		}	
		$a = self::normalize_atts($atts);
		$sort = self::build_mileage_sort($a);
		$min = self::get_minimum_val($a);
		$meta = PwtcMileage_DB::meta_ly_miles();
		$data = PwtcMileage_DB::fetch_ly_miles(ARRAY_N, $sort, $min);
		$out = self::shortcode_build_table($meta, $data, $a, $content);
		return $out;
	}

	// Generates the [pwtc_mileage_lifetime] shortcode.
	public static function shortcode_lt_miles($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "<div>Please log in to see the lifetime mileage report.</div>";
		}	
		$a = self::normalize_atts($atts);
		$sort = self::build_mileage_sort($a);
		$min = self::get_minimum_val($a);
		$meta = PwtcMileage_DB::meta_lt_miles();
		$data = PwtcMileage_DB::fetch_lt_miles(ARRAY_N, $sort, $min);
		$out = self::shortcode_build_table($meta, $data, $a, $content);
		return $out;
	}

	// Generates the [pwtc_rides_led_year_to_date] shortcode.
	public static function shortcode_ytd_led($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "<div>Please log in to see the year-to-date number of rides led report.</div>";
		}	
		$a = self::normalize_atts($atts);
		$sort = self::build_rides_led_sort($a);
		$min = self::get_minimum_val($a);
		$meta = PwtcMileage_DB::meta_ytd_led();
		$data = PwtcMileage_DB::fetch_ytd_led(ARRAY_N, $sort, $min);
		$out = self::shortcode_build_table($meta, $data, $a, $content);
		return $out;
	}

	// Generates the [pwtc_rides_led_last_year] shortcode.
	public static function shortcode_ly_led($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "<div>Please log in to see the last year number of rides led report.</div>";
		}	
		$a = self::normalize_atts($atts);
		$sort = self::build_rides_led_sort($a);
		$min = self::get_minimum_val($a);
		$meta = PwtcMileage_DB::meta_ly_led();
		$data = PwtcMileage_DB::fetch_ly_led(ARRAY_N, $sort, $min);
		$out = self::shortcode_build_table($meta, $data, $a, $content);
		return $out;
	}

	// Generates the [pwtc_rides_year_to_date] shortcode.
	public static function shortcode_ytd_rides($atts, $content = null) {
		$out = '<div>';
		try {
			$member_id = pwtc_mileage_get_member_id();
			$name = self::get_rider_name($member_id);
			$a = self::normalize_atts($atts);
			$meta = PwtcMileage_DB::meta_ytd_rides($name);
			$data = PwtcMileage_DB::fetch_ytd_rides(ARRAY_N, $member_id);
			$out .= self::shortcode_build_table($meta, $data, $a, $content);
		}
		catch (Exception $e) {
			switch ($e->getMessage()) {
				case "notloggedin":
					$out .= 'Please log in to view your year-to-date rides.';
					break;
				case "idnotfound":
					$out .= 'Cannot view your year-to-date rides, rider ID not found.';
					break;
				case "multidfound":
					$out .= 'Cannot view your year-to-date rides, multiple rider IDs found.';
					break;
				default:
					$out .= 'Cannot view your year-to-date rides, unknown error.';
			}
		}
		$out .= '</div>';
		return $out;
	}

	// Generates the [pwtc_rides_last_year] shortcode.
	public static function shortcode_ly_rides($atts, $content = null) {
		$out = '<div>';
		try {
			$member_id = pwtc_mileage_get_member_id();
			$name = self::get_rider_name($member_id);
			$a = self::normalize_atts($atts);
			$meta = PwtcMileage_DB::meta_ly_rides($name);
			$data = PwtcMileage_DB::fetch_ly_rides(ARRAY_N, $member_id);
			$out .= self::shortcode_build_table($meta, $data, $a, $content);
		}
		catch (Exception $e) {
			switch ($e->getMessage()) {
				case "notloggedin":
					$out .= 'Please log in to view your last year rides.';
					break;
				case "idnotfound":
					$out .= 'Cannot view your last year rides, rider ID not found.';
					break;
				case "multidfound":
					$out .= 'Cannot view your last year rides, multiple rider IDs found.';
					break;
				default:
					$out .= 'Cannot view your last year rides, unknown error.';
			}
		}
		$out .= '</div>';
		return $out;
	}

	// Generates the [pwtc_led_rides_year_to_date] shortcode.
	public static function shortcode_ytd_led_rides($atts, $content = null) {
		$out = '<div>';
		try {
			$member_id = pwtc_mileage_get_member_id();
			$name = self::get_rider_name($member_id);
			$a = self::normalize_atts($atts);
			$meta = PwtcMileage_DB::meta_ytd_rides_led($name);
			$data = PwtcMileage_DB::fetch_ytd_rides_led(ARRAY_N, $member_id);
			$out .= self::shortcode_build_table($meta, $data, $a, $content);
		}
		catch (Exception $e) {
			switch ($e->getMessage()) {
				case "notloggedin":
					$out .= 'Please log in to view your year-to-date rides led.';
					break;
				case "idnotfound":
					$out .= 'Cannot view your year-to-date rides led, rider ID not found.';
					break;
				case "multidfound":
					$out .= 'Cannot view your year-to-date rides led, multiple rider IDs found.';
					break;
				default:
					$out .= 'Cannot view your year-to-date rides led, unknown error.';
			}
		}
		$out .= '</div>';
		return $out;
	}

	// Generates the [pwtc_led_rides_last_year] shortcode.
	public static function shortcode_ly_led_rides($atts, $content = null) {
		$out = '<div>';
		try {
			$member_id = pwtc_mileage_get_member_id();
			$name = self::get_rider_name($member_id);
			$a = self::normalize_atts($atts);
			$meta = PwtcMileage_DB::meta_ly_rides_led($name);
			$data = PwtcMileage_DB::fetch_ly_rides_led(ARRAY_N, $member_id);
			$out .= self::shortcode_build_table($meta, $data, $a, $content);
		}
		catch (Exception $e) {
			switch ($e->getMessage()) {
				case "notloggedin":
					$out .= 'Please log in to view your last year rides led.';
					break;
				case "idnotfound":
					$out .= 'Cannot view your last year rides led, rider ID not found.';
					break;
				case "multidfound":
					$out .= 'Cannot view your last year rides led, multiple rider IDs found.';
					break;
				default:
					$out .= 'Cannot view your last year rides led, unknown error.';
			}
		}
		$out .= '</div>';
		return $out;
	}

	// Generates the [pwtc_posted_rides_wo_sheets] shortcode.
	public static function shortcode_rides_wo_sheets($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "<div>Please log in to see the posted rides that are missing ridesheets report.</div>";
		}	
		$a = self::normalize_atts($atts);
		$meta = PwtcMileage_DB::meta_posts_without_rides2();
		$data = PwtcMileage_DB::fetch_posts_without_rides2();
		$out = self::shortcode_build_table($meta, $data, $a, $content);
		return $out;
	}

	// Generates the [pwtc_ridecal_download] shortcode.
	public static function shortcode_ridecal_download($atts, $content = null) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return "";
		}	
    	$a = shortcode_atts(array(
			'month' => 'current',
			'file' => 'pdf'
		), $atts);
		$time = current_time('timestamp');
		if ($a['month'] == 'next') {
			$time2 = strtotime('+1 day', strtotime(date('Y-m-t', $time)));
			$start = date('Y-m-01', $time2);
			$end = date('Y-m-t', $time2);
			$monthname = date('M', $time2);
		}
		else {
			$start = date('Y-m-01', $time);
			$end = date('Y-m-t', $time);
			$monthname = date('M', $time);	
		}
		if ($a['file'] == 'csv') {
			$file = 'csv';
		}
		else {
			$file = 'pdf';
		}
		$label = $monthname . " Rides (" . $file . ")";	
		$out = '<form style="display: inline" method="POST">';
		$out .= '<input class="dark button" type="submit" name="download_ridecal" value="' . $label . '"/>';
		$out .= '<input type="hidden" name="start" value="' . $start . '"/>';
		$out .= '<input type="hidden" name="end" value="' . $end . '"/>';
		$out .= '<input type="hidden" name="file" value="' . $file . '"/>';
		$out .= '</form>';
		return $out;
	}
		
	// Generates the [pwtc_riderid_download] shortcode.
	public static function shortcode_riderid_download($atts, $content = null) {
		$out = '';
		try {
			$member_id = pwtc_mileage_get_member_id();
			$result = PwtcMileage_DB::fetch_rider($member_id);
			if (count($result) == 0) {
				$out .= 'Cannot download rider ID card, fetch of details for rider ' . 
					$member_id . ' failed.';
			}
			else {
				$lastname = $result[0]['last_name'];
				$firstname = $result[0]['first_name'];
				$exp_date = $result[0]['expir_date'];
				$fmtdate = date('M Y', strtotime($exp_date));
				$out .= '<form style="display: inline" method="POST">';
				$out .= '<input class="dark button" type="submit" name="download_riderid" value="Rider ID"/>';
				$out .= '<input type="hidden" name="rider_id" value="' . $member_id . '"/>';
				$out .= '<input type="hidden" name="rider_name" value="' . $firstname . ' ' . $lastname . '"/>';
				$out .= '<input type="hidden" name="expire_date" value="' . $fmtdate . '"/>';
				$out .= '</form>';
			}
		}
		catch (Exception $e) {
			switch ($e->getMessage()) {
				case "notloggedin":
					//$out = '<span title="log in to download your rider ID card">Error!</span>';
					break;
				case "idnotfound":
					$out .= 'Cannot download rider ID card, rider ID not found.';
					break;
				case "multidfound":
					$out .= 'Cannot download rider ID card, multiple rider IDs found.';
					break;
				default:
					$out .= 'Cannot download rider ID card, unknown error.';
			}
		}
		return $out;
	}

	/*************************************************************/
	/* User input validation functions
	/*************************************************************/

	public static function validate_date_str($datestr) {
		$ok = true;
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datestr) !== 1) {
			$ok = false;
		}
		return $ok;
	}

	public static function validate_member_id_str($memberid) {
		$ok = true;
		if (preg_match('/^\d{5}$/', $memberid) !== 1) {
			$ok = false;
		}
		return $ok;
	}

	public static function validate_member_name_str($name) {
		$ok = true;
		if (preg_match('/^[A-Za-z].*/', $name) !== 1) {
			$ok = false;
		}
		return $ok;
	}

	public static function validate_ride_title_str($title) {
		$ok = true;
		if (preg_match('/^[A-Za-z0-9].*/', $title) !== 1) {
			$ok = false;
		}
		return $ok;
	}

	public static function validate_label_str($label) {
		$ok = true;
		if (preg_match('/^[A-Za-z].*/', $label) !== 1) {
			$ok = false;
		}
		return $ok;
	}

	public static function validate_mileage_str($mileage) {
		$ok = true;
		if (!is_numeric($mileage)) {
			$ok = false;
		}
		else if (intval($mileage) < 0) {
			$ok = false;
		}
		return $ok;
	}

	public static function validate_number_str($number) {
		$ok = true;
		if (!is_numeric($number)) {
			$ok = false;
		}
		else if (intval($number) < 0) {
			$ok = false;
		}
		return $ok;
	}

	/*************************************************************/
	/* Plugin options access functions
	/*************************************************************/

	public static function create_default_plugin_options() {
		$data = array(
			'drop_db_on_delete' => false,
			'plugin_menu_label' => 'Rider Mileage',
			'plugin_menu_location' => 50,
			'ride_lookback_date' => '',
			'disable_expir_check' => false,
			'disable_delete_confirm' => false,
			'show_ride_ids' => false,
			'expire_grace_period' => 60,
			'db_lock_time_limit' => 60);
		add_option('pwtc_mileage_options', $data);
	}

	public static function get_plugin_options() {
		return get_option('pwtc_mileage_options');
	}

	public static function delete_plugin_options() {
		delete_option('pwtc_mileage_options');
	}

	public static function update_plugin_options($data) {
		update_option('pwtc_mileage_options', $data);
	}

	public static function get_date_for_expir_check() {
		$plugin_options = self::get_plugin_options();
		$time = $plugin_options['expire_grace_period'] * 24 * 60 * 60; // convert grace period from days to seconds
		return date('Y-m-d', current_time('timestamp') - $time);
	}

	/*************************************************************/
	/* Plugin capabilities management functions for admin role.
	/*************************************************************/

	public static function add_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->add_cap(self::VIEW_MILEAGE_CAP);
		$admin->add_cap(self::EDIT_MILEAGE_CAP);
		$admin->add_cap(self::EDIT_RIDERS_CAP);
		$admin->add_cap(self::DB_OPS_CAP);
		pwtc_mileage_write_log('PWTC Mileage plugin added capabilities to administrator role');
	}

	public static function remove_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->remove_cap(self::VIEW_MILEAGE_CAP);
		$admin->remove_cap(self::EDIT_MILEAGE_CAP);
		$admin->remove_cap(self::EDIT_RIDERS_CAP);
		$admin->remove_cap(self::DB_OPS_CAP);
		pwtc_mileage_write_log('PWTC Mileage plugin removed capabilities from administrator role');
	}

	/*************************************************************/
	/* Plugin installation and removal functions.
	/*************************************************************/

	public static function plugin_activation() {
		pwtc_mileage_write_log( 'PWTC Mileage plugin activated' );
		if ( version_compare( $GLOBALS['wp_version'], PWTC_MILEAGE__MINIMUM_WP_VERSION, '<' ) ) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC Mileage plugin requires Wordpress version of at least ' . PWTC_MILEAGE__MINIMUM_WP_VERSION);
		}
		$errs = PwtcMileage_DB::create_db_tables();
		if ($errs > 0) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC Mileage plugin could not create database tables');			
		}
		$errs = PwtcMileage_DB::create_db_views();
		if ($errs > 0) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC Mileage plugin could not create database views');			
		}
		if (self::get_plugin_options() === false) {
			//self::delete_plugin_options();
			self::create_default_plugin_options();
		}
		self::add_caps_admin_role();
		pwtc_mileage_create_stat_role();
	}

	public static function plugin_deactivation( ) {
		pwtc_mileage_write_log( 'PWTC Mileage plugin deactivated' );
		//self::delete_plugin_options();
		self::remove_caps_admin_role();
		pwtc_mileage_remove_stat_role();
		/*
		$plugin_options = self::get_plugin_options();
		if ($plugin_options['drop_db_on_delete']) {
			PwtcMileage_DB::drop_db_views();	
			PwtcMileage_DB::drop_db_tables();				
		}
		*/
	}

	public static function plugin_uninstall() {
		pwtc_mileage_write_log( 'PWTC Mileage plugin uninstall' );	
		$plugin_options = self::get_plugin_options();
		if ($plugin_options['drop_db_on_delete']) {
			PwtcMileage_DB::drop_db_views();	
			PwtcMileage_DB::drop_db_tables();				
		}
		self::delete_plugin_options();
	}

}