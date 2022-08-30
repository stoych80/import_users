<?php
/*
Plugin Name: Import users
Plugin URI: https://github.com/stoych80/import_users
Description: Import users.
Version: 1.0.0
Author: Stoycho Stoychev
Depends: 
--------------------------------------------------------------------------------
*/
defined('ABSPATH') || die('do not access this file directly');

class dd_import_users {

	// class instance
	private static $instance;
	private static $global_delimiter = '|==|';

	public function __construct() {
		if (is_admin()) {
			add_action('admin_menu', function () {
				$hook = add_submenu_page(
					'users.php',
					'DD Import Users',
					'DD Import Users',
					'manage_options',
					'dd_import_users',
					array($this, 'dd_import_users_page')
				);
				add_action("load-$hook", array($this, 'screen_option'));
			});
			add_action('admin_init', function() {
				$this->dd_import_users_run_step_3_ajax();
				if (isset($_GET['load_fields_profile']) && $_GET['load_fields_profile']==1) {
					if (empty($_GET['profile_name'])) {
						die('No Profile is selected.');
					}
					$wp_user_id = get_current_user_id();
					if (!($dd_import_users_fields_profiles = get_user_meta($wp_user_id, 'dd_import_users_fields_profiles', true))) {
						$dd_import_users_fields_profiles = array();
					}
					if (!in_array($_GET['profile_name'],array_keys($dd_import_users_fields_profiles))) {
						die('Profile name "'.$_GET['profile_name'].'" not found.');
					}
					echo (string) json_encode($dd_import_users_fields_profiles[$_GET['profile_name']]);exit;
				}
				if (isset($_GET['delete_fields_profile']) && $_GET['delete_fields_profile']==1) {
					if (empty($_GET['profile_name'])) {
						die('No Profile is selected.');
					}
					$wp_user_id = get_current_user_id();
					if (!($dd_import_users_fields_profiles = get_user_meta($wp_user_id, 'dd_import_users_fields_profiles', true))) {
						$dd_import_users_fields_profiles = array();
					}
					if (!in_array($_GET['profile_name'],array_keys($dd_import_users_fields_profiles))) {
						die('Profile name "'.$_GET['profile_name'].'" not found.');
					}
					unset($dd_import_users_fields_profiles[$_GET['profile_name']]);
					update_user_meta($wp_user_id,'dd_import_users_fields_profiles',$dd_import_users_fields_profiles);
					die('success');
				}
				if (isset($_GET['update_fields_profile']) && $_GET['update_fields_profile']==1) {
					if (empty($_GET['profile_name'])) {
						die('Profile name cannot be empty.');
					}
					if (empty($_GET['old_profile_name'])) {
						die('Old Profile name cannot be empty.');
					}
					$wp_user_id = get_current_user_id();
					if (!($dd_import_users_fields_profiles = get_user_meta($wp_user_id, 'dd_import_users_fields_profiles', true))) {
						$dd_import_users_fields_profiles = array();
					}
					if (!in_array($_GET['old_profile_name'],array_keys($dd_import_users_fields_profiles))) {
						die('Old Profile name "'.$_GET['old_profile_name'].'" not found.');
					}
					if (!ctype_alnum(str_replace(' ', '', $_GET['profile_name']))) {
						die('Profile name can only consist of letters and numbers.');
					}
					if (!ctype_alnum(str_replace(' ', '', $_GET['old_profile_name']))) {
						die('Old Profile name can only consist of letters and numbers.');
					}
					unset($dd_import_users_fields_profiles[$_GET['old_profile_name']]);
					$user_fields = self::get_user_fields_all();
					$profile_fields = array();
					foreach ($user_fields as $f) {
						$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
						$profile_fields[$f]=isset($_POST[$f_post]) ? esc_attr($_POST[$f_post]) : '';
					}
					$dd_import_users_fields_profiles[$_GET['profile_name']] = $profile_fields;
					update_user_meta($wp_user_id,'dd_import_users_fields_profiles',$dd_import_users_fields_profiles);
					die('success');
				}
				if (isset($_GET['create_fields_profile']) && $_GET['create_fields_profile']==1) {
					if (empty($_GET['profile_name'])) {
						die('Profile name cannot be empty.');
					}
					$wp_user_id = get_current_user_id();
					if (!($dd_import_users_fields_profiles = get_user_meta($wp_user_id, 'dd_import_users_fields_profiles', true))) {
						$dd_import_users_fields_profiles = array();
					}
					if (in_array($_GET['profile_name'],array_keys($dd_import_users_fields_profiles))) {
						die('Profile name "'.$_GET['profile_name'].'" already exist.');
					}
					if (!ctype_alnum(str_replace(' ', '', $_GET['profile_name']))) {
						die('Profile name can only consist of letters and numbers.');
					}
					$user_fields = self::get_user_fields_all();
					$profile_fields = array();
					foreach ($user_fields as $f) {
						$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
						$profile_fields[$f]=isset($_POST[$f_post]) ? esc_attr($_POST[$f_post]) : '';
					}
					$dd_import_users_fields_profiles[$_GET['profile_name']] = $profile_fields;
					update_user_meta($wp_user_id,'dd_import_users_fields_profiles',$dd_import_users_fields_profiles);
					die('success');
				}
			});
			if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'dd_import_users')
			add_action('admin_enqueue_scripts', function () {
				$plugin_data = get_plugin_data(__FILE__);
				wp_register_style('dd_import_users-admin', '/wp-content/plugins/dd_import_users/css/admin.css', array(), $plugin_data['Version']);
				wp_enqueue_style('dd_import_users-admin');
				wp_register_script('dd_import_users-admin', '/wp-content/plugins/dd_import_users/js/admin.js', array(), $plugin_data['Version']);
				wp_enqueue_script('dd_import_users-admin');
			});
			add_action('wp_loaded', array($this, 'wp_loaded_dd_import_users'));
			add_action('admin_notices', array($this, 'admin_notices_dd_import_users'));
		} else {
			add_action('wp_head', function () {
				if (isset($_GET['dd_import_users_msg'])) : ?>
					<script type="text/javascript">
					jQuery(function ($) {
						$('<div></div>').appendTo('body').html('<div><?=esc_js($_GET['dd_import_users_msg']);?></div>').dialog({
							modal: true,
							title: 'Error',
							zIndex: 10000,
							autoOpen: true,
							width: '90%',
							resizable: false,
//							position: {my:'top',at:'top+190'},
							buttons: {
								Ok: function () {
									$(this).dialog("close");
									$(this).dialog('destroy').remove();
								}
							},
							closeOnEscape: true,
							open: function(ev, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();}
						});
					});
					</script>
				<?php
				endif;
			},51);
			add_action('wp', function () {
				if (is_user_logged_in()) {
					$wp_user_id = get_current_user_id();
					$wp_user = get_user_by('id', $wp_user_id);
					if (substr($_SERVER['REQUEST_URI'],0,strlen('/members/'.urlencode($wp_user->user_nicename).'/profile/edit/group/'))!='/members/'.urlencode($wp_user->user_nicename).'/profile/edit/group/' && get_user_meta($wp_user_id, 'dd_import_users_force_to_fill_required_fields', true)) {
					global $wpdb;
					//check if the user has any required fields not filled in
					if(($req_fields_to_fill=$wpdb->get_col($wpdb->prepare('SELECT name FROM wp_bp_xprofile_fields WHERE type!=\'option\' AND is_required=1 AND id NOT IN (SELECT field_id FROM wp_bp_xprofile_data WHERE user_id=%d AND value IS NOT NULL AND value!=\'\' AND value!=\'a:0:{}\')'.apply_filters('dd_import_users_extra_sql_whether_to_fill_required_fields','',$wp_user_id),$wp_user_id)))) {
						wp_safe_redirect('/members/'.urlencode($wp_user->user_nicename).'/profile/edit/group/1/?dd_import_users_msg='.urlencode('Please fill in the following required fields in your profile: '.implode(', ', $req_fields_to_fill)));exit;
					} else {
						update_user_meta($wp_user_id, 'dd_import_users_force_to_fill_required_fields', 0);
					}
					}
				}
			});
		}
//		add_action('admin_head', array($this, 'admin_head_custom'), 50);
	}
//	public function admin_head_custom() {
//		$plugin_data = get_plugin_data(__FILE__);
//	}
	
	public static function upload_dir_temp($dir) {
		return array(
			'path'   => $dir['basedir'] .'/'. __CLASS__,
			'url'    => $dir['baseurl'] .'/'. __CLASS__,
			'subdir' => '/'. __CLASS__,
		) + $dir;
	}
	public static function unique_filename_callback($dir, $name, $ext) {
		return $name.(strpos($name, '.')===false ? $ext : '');
	}
	private static function get_user_fields_required() {
		global $wpdb;
		return array_merge(array(
			'user_login',
			'user_email',
			'first_name',
			'last_name',
		), $wpdb->get_var("SHOW TABLES LIKE 'wp_bp_xprofile_fields'") ? $wpdb->get_col("SELECT CONCAT('bpfield__',id) FROM wp_bp_xprofile_fields WHERE type!='option' AND is_required=1") : array());
	}
	private static function get_user_fields_regular() {
		return array(
			'user_login',
			'user_pass',
			'user_nicename',
			'user_email',
			'display_name',
			'role',
		);
	}
	private static function get_user_fields_meta() {
		return apply_filters('dd_import_users_get_user_fields_meta',array(
			'first_name',
			'last_name',
		));
	}
	private static function get_user_fields_bp() {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE 'wp_bp_xprofile_fields'")) {
			return $wpdb->get_col("SELECT CONCAT('bpfield__',id) FROM wp_bp_xprofile_fields WHERE type!='option' ORDER BY group_id,name");
		}
		return array();
	}
	private static function get_user_fields_pmpro() {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE 'wp_pmpro_membership_levels'")) {
			return array('PmPro Level','Membership Start Date','Membership Expiry Date');
		}
		return array();
	}
	private static function get_user_fields_hints() {
		global $wpdb;
		$return = array(
			'user_login'=>'This is identifier, i.e. existing users will be updated based on it (if "Skip Existing Users" is unticked). Multiple values here will NOT be combined - each of them will be checked and the 1st match will be used. This one takes precedence over user_email i.e. if user is identified based on both this one will be used.',
			'user_email'=>'This is identifier, i.e. existing users will be updated based on it (if "Skip Existing Users" is unticked). Multiple values here will NOT be combined - each of them will be checked and the 1st match will be used. user_login takes precedence over this one i.e. if user is identified based on both user_login will be used.'
		);
		if($wpdb->get_var("SHOW TABLES LIKE 'wp_bp_xprofile_fields'")) {
			$results=$wpdb->get_results("SELECT CONCAT('bpfield__',id) AS name,description FROM wp_bp_xprofile_fields WHERE type!='option' AND description IS NOT NULL AND description!=''");
			foreach ($results as $result) {
				$return[$result->name]=$result->description;
			}
		}
		if($wpdb->get_var("SHOW TABLES LIKE 'wp_pmpro_membership_levels'")) {
			$return['PmPro Level']='Value must be one of: '.implode(', ', $wpdb->get_col("SELECT name FROM wp_pmpro_membership_levels"));
			$return['Membership Start Date']='If membership level is applicable, this value is always present, even if the field is not mapped or if there is no default value - todays date will be used.';
			$return['Membership Expiry Date']='If membership level is applicable, even if the field is not mapped - the default value selected will be applied.';
		}
		return apply_filters('dd_import_users_get_user_fields_hints',$return);
	}
	private static function get_user_fields_default_values() {
		global $wpdb;
		$return = array();
		
		$fields=self::get_user_fields_regular();
		foreach ($fields as $f) {
			if ($f=='user_login' || $f=='user_email') continue;
			if ($f=='role') {
				$roles = get_editable_roles();
				$roles = array_reverse($roles, true);
				$default_role =$default_role_name= get_option('default_role');
				if (isset($roles[$default_role])) {
					$default_role_name=$roles[$default_role]['name'];
				}
				$options_html = '<select name="role_default_value" style="width:200px;"><option value="'.esc_attr($default_role).'">the default role '.esc_attr($default_role_name).'</option><option value="">No role</option>';
				foreach ($roles as $role_name => $role_info) {
					$options_html .= '<option value="'.esc_attr($role_name).'">'.esc_attr($role_info['name']).'</option>';
				}
				$return[$f]='<b>If the csv value is empty or invalid use</b>: '.$options_html.'</select>';
			} else $return[$f] = '<b>If the csv value is empty or invalid use</b>: <input type="text" name="'.$f.'_default_value" style="width:200px;" value="" />';
		}
		$fields=self::get_user_fields_meta();
		foreach ($fields as $f) {
			$return[$f] = '<b>If the csv value is empty or invalid use</b>: <input type="text" name="'.$f.'_default_value" style="width:200px;" value="" />';
		}
		$fields=self::get_user_fields_bp();
		foreach ($fields as $f) {
			$return[$f] = '<b>If the csv value is empty or invalid use</b>: <input type="text" name="'.$f.'_default_value" style="width:200px;" value="" />';
		}
		
		if($wpdb->get_var("SHOW TABLES LIKE 'wp_pmpro_membership_levels'")) {
			$levels=$wpdb->get_col("SELECT name FROM wp_pmpro_membership_levels");
			$levels_options_html = '<option value="">-- Skip Membership --</option>';
			foreach ($levels as $level_name) {
				$levels_options_html .= '<option value="'.esc_attr($level_name).'">'.esc_attr($level_name).'</option>';
			}
			$return['PmPro Level']='<b>If the csv value is empty or invalid use</b>: <select name="pmpro_level_default_value" style="width:200px;">'.$levels_options_html.'</select>';
			$return['Membership Start Date']='<b>If the csv value is empty or invalid use</b>: <input type="text" name="membership_start_date_default_value" style="width:200px;" value="'.date('d/m/Y').'" />';
			$return['Membership Expiry Date']='<b>If the csv value is empty or invalid</b>: <select name="membership_expiry_date_default_value" style="width:200px;"><option value="leave_empty">Leave Empty (i.e. membership won\'t expire)</option><option value="expire_today">Expire membership today</option><option value="membership_start_date_and_length_of_the_user_membership">Use "Membership Start Date" + the length of the user\'s membership</option><option value="todays_date_and_length_of_the_user_membership">Use Todays date + the length of the user\'s membership</option></select>';
		}
		return apply_filters('dd_import_users_get_user_fields_default_values',$return);
	}
	private static function get_user_fields_all() {
		return array_merge(self::get_user_fields_regular(), self::get_user_fields_meta(), self::get_user_fields_bp(), self::get_user_fields_pmpro());
	}
	public function dd_import_users_run_step_3_ajax() {
		if (isset($_POST['user_login']) && isset($_REQUEST['iterate_start']) && isset($_REQUEST['step']) && $_REQUEST['step']==2) {
			$filename = ABSPATH.'wp-content/uploads/'.__CLASS__.'/'.__CLASS__.'.csv';
			add_filter('send_password_change_email', function ($sendit, $user, $userdata) {
				$sendit=false;
				return $sendit;
			},10,3);
			add_filter('send_email_change_email', function ($sendit, $user, $userdata) {
				$sendit=false;
				return $sendit;
			},10,3);
			global $wpdb;
			$handle = @fopen($filename, "r");
			if (!$handle) {
				die('File ' . $filename.' not found');
			}
			ini_set('memory_limit','1512M');
			set_time_limit(0);
			$site_name = get_option('blogname');
			$site_url = get_option('siteurl');
			$errors = isset($_SESSION['dd_import_users_errors']) ? $_SESSION['dd_import_users_errors'] : array();
			$warnings = isset($_SESSION['dd_import_users_warnings']) ? $_SESSION['dd_import_users_warnings'] : array();
			$inserted = isset($_SESSION['dd_import_users_inserted']) ? $_SESSION['dd_import_users_inserted'] : 0;
			$updated = isset($_SESSION['dd_import_users_updated']) ? $_SESSION['dd_import_users_updated'] : 0;
			$skipped = isset($_SESSION['dd_import_users_skipped']) ? $_SESSION['dd_import_users_skipped'] : array();
			$user_fields_required = self::get_user_fields_required();
			$missing_required_headers = array();
			foreach ($user_fields_required as $f) {
				$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
				if (empty($_POST[$f_post]) && empty($_POST[$f_post.'_default_value'])) $missing_required_headers[] = $f;
			}
			if (empty($_POST['if_required_fields_are_missing']) && !empty($missing_required_headers)) {
				die('Please map the required User fields with CSV Fields: '.implode(', ', $missing_required_headers));
//				wp_safe_redirect('/wp-admin/users.php?page=dd_import_users&step=2&msg_dd_import_users='.urlencode('Please map the required User fields with CSV Fields: '.implode(', ', $missing_required_headers)));exit;
			} else if (!empty($_POST['if_required_fields_are_missing']) && (empty($_POST['user_login']) || empty($_POST['user_email']))) {
				die('Even with "Import users if required fields are missing" enabled you still have to specify the identifiers - user_login and user_email');
//				wp_safe_redirect('/wp-admin/users.php?page=dd_import_users&step=2&msg_dd_import_users='.urlencode('Even with "required fields missing" you still have to specify the identifiers - user_login and user_email'));exit;
			}
			if (($data = fgetcsv($handle)) !== FALSE) {
				$csv_headers = array_map('trim', $data);
			}
			$csv_headers_with_comma = array();
			foreach ($csv_headers as $csv_header) {
				if (strpos($csv_header, ',') !== false) {
					$csv_headers_with_comma[]=$csv_header;
				}
			}
			if ($csv_headers_with_comma) {
				die('CSV headers cannot contain comma as comma is used for multiple values delimiter:<ul style="list-style:disc;margin-left:10px;"><li>'.implode('</li><li>',$csv_headers_with_comma).'</li></ul>');
			}
			//check if the csv names written in the fields match what's in the csv headers
			$user_fields = self::get_user_fields_all();
			$invalid_csv_fields = array();
			foreach ($user_fields as $f) {
				$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
				if (empty($_POST[$f_post])) continue;
				$f_post=array_map('trim',explode(',',$_POST[$f_post]));
				foreach ($f_post as $f_post2) {
					if (!in_array($f_post2, $csv_headers) && !in_array($f, $invalid_csv_fields)) {
						$invalid_csv_fields[]=$f;
					}
				}
			}
			if ($invalid_csv_fields) {
				die('The fields below contain names that do not exist in the CSV headers:<ul style="list-style:disc;margin-left:10px;"><li>'.implode('</li><li>',$invalid_csv_fields).'</li></ul>');
			}
			$line=2;
			$iterate_start=isset($_REQUEST['iterate_start']) ? $_REQUEST['iterate_start'] : 2;
			$iterate_end=$iterate_start+$_REQUEST['process_records_per_query'];
			while (($data = fgetcsv($handle)) !== FALSE) {
				if ($iterate_start>$line) {
					$line++;
					continue;
				}
				if ($iterate_end<=$line) {
					$_SESSION['dd_import_users_errors']=$errors;
					$_SESSION['dd_import_users_warnings']=$warnings;
					$_SESSION['dd_import_users_inserted']=$inserted;
					$_SESSION['dd_import_users_updated']=$updated;
					$_SESSION['dd_import_users_skipped']=$skipped;
					fclose($handle);
					die('dd_import_users_next_iterate');
				}
				$data = array_map('trim', $data);
				$importit = true;
				$skipped_string = 'Line '.$line.':';
				foreach ($user_fields_required as $f) {
					$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
					$f_post=array_map('trim',explode(',',$_POST[$f_post]));
					$has_data=false;
					foreach ($f_post as $f_post2) {
						if ((empty($_POST['if_required_fields_are_missing']) || $f=='user_login' || $f=='user_email') && ($index = array_search($f_post2, $csv_headers))!==false && empty($data[$index]) && empty($_POST[$f_post2.'_default_value'])) {
							
						} else  {
							$has_data=true;break 1;
						}
					}
					if (!$has_data) {
						$importit = false;
						$skipped_string .= "\n".'   "'.$f.'" is a required field.';
					}
				}
				$wp_user_id = null;
				$f_post=array_map('trim',explode(',',$_POST['user_email']));
				$email_matched = false;
				$email=null;
				foreach ($f_post as $f_post2) {
					$email_check = apply_filters('dd_import_users_field_value_pre_import',($email_index = array_search($f_post2, $csv_headers))!==false ? $data[$email_index] : null, 'user_email', $csv_headers, $data);
					if (empty($email)) $email = $email_check;
					if (!empty($email_check) && email_exists($email_check)) {
						$wp_user = get_user_by('email', $email_check);
						$wp_user_id = $wp_user->ID;
						$email=$email_check;
						$email_matched = true;
						break 1;
					}
				}
				if ($email_matched && isset($_POST['skip_existing_users'])) {
					$importit = false;
					$skipped_string .= "\n".'   User with Email '.$email.' already exists.';
				}
				
				$f_post=array_map('trim',explode(',',$_POST['user_login']));
				$username_matched = false;
				$username=null;
				foreach ($f_post as $f_post2) {
					$username_check = apply_filters('dd_import_users_field_value_pre_import', ($username_index = array_search($f_post2, $csv_headers))!==false ? $data[$username_index] : null, 'user_login', $csv_headers, $data);
					if (empty($username)) $username = $username_check;
					if (!empty($username_check) && username_exists($username_check)) {
						$wp_user = get_user_by('login', $username_check);
						$wp_user_id = $wp_user->ID;
						$username = $username_check;
						$username_matched = true;
						break 1;
					}
				}
				if ($email_matched && !$username_matched) {
					$username = $email;
				}
				if ($username_matched && isset($_POST['skip_existing_users'])) {
					$importit = false;
					$skipped_string .= "\n".'   User with Username '.$username.' already exists.';
				}
				if (!$importit) {
					$skipped[]=$skipped_string;
				} else {
					$user_fields_regular = self::get_user_fields_regular();
					$user_fields_meta = self::get_user_fields_meta();
					$user_fields_regular_meta = array_merge($user_fields_regular, $user_fields_meta);
					$update_user_arr=array('user_login'=>$username, 'user_email'=>$email);
					foreach ($user_fields_regular_meta as $f) {
						if ($f == 'user_login' || $f == 'user_email') continue;
						$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
						if (empty($_POST[$f_post]) && !in_array($f, $user_fields_regular)) continue;
						$f_post1=array_map('trim',explode(',',$_POST[$f_post]));
						$f_data='';
						foreach ($f_post1 as $f_post2) {
							$f_data.= $f_data!= '' ? ' ' : '';
							//manipulate the field value for the client based on some criteria i.e. import membership number only for Full members
							$f_data.=apply_filters('dd_import_users_field_value_pre_import',($index = array_search($f_post2, $csv_headers))!==false ? $data[$index] : '', $f_post, $csv_headers, $data);
						}
						$default_value=$_POST[$f_post.'_default_value'];
						if ($f=='role') {
							if (empty($f_data)) {
								$f_data = $default_value;
							} else {
								$roles = get_editable_roles();
								$found=false;
								foreach ($roles as $role_name => $role_info) {
									if ($f_data==$role_name || $f_data==$role_info['name']) {
										$found=true;break 1;
									}
								}
								if (!$found) {
									$warnings[] = 'Line '.$line.': User ('.$username.') has invalid value for "'.$f.'" - '.$f_data.'. Default value "'.$default_value.'" has been used.';
									$f_data = $default_value;
								}
							}
						} elseif ($f=='display_name') {
							if (empty($f_data)) $f_data = $default_value;
							if (empty($f_data) && $wp_user_id) {
								$wp_user = get_user_by('id', $wp_user_id);
								$f_data=$wp_user->display_name;$wp_user=null;
							}
							if (empty($f_data)) {
								foreach (array('first_name', 'last_name') as $build_display_name)
								if (!empty($_POST[$build_display_name])) {
									$f_post1=array_map('trim',explode(',',$_POST[$build_display_name]));
									foreach ($f_post1 as $f_post2) {
										$f_data.= $f_data!= '' ? ' ' : '';
										//manipulate the field value for the client based on some criteria i.e. import membership number only for Full members
										$f_data.=apply_filters('dd_import_users_field_value_pre_import',($index = array_search($f_post2, $csv_headers))!==false ? $data[$index] : '', $f_post, $csv_headers, $data);
									}
								}
							}
						} else {
							if (empty($f_data)) $f_data = $default_value;
						}
						$update_user_arr[$f] = $f_data;
					}
					if ($wp_user_id) {
						$update_user_arr = array_merge($update_user_arr,array('ID'=>$wp_user_id));
						if (!isset($_POST['simulation']))
							wp_update_user($update_user_arr);
						//user_login is not updated by the function above so we do it manually
						$wpdb->update($wpdb->users, array('user_login'=>$username), array('ID'=>$wp_user_id));
						$updated++;
					} else {
						if (!isset($_POST['simulation']))
							$wp_user_id = wp_insert_user($update_user_arr);
						$inserted++;
					}
					if ($_POST['if_required_fields_are_missing']==1 && !isset($_POST['simulation'])) {
						update_user_meta($wp_user_id, 'dd_import_users_force_to_fill_required_fields', 1);
					}
					if (is_wp_error($wp_user_id)) {
						 $errors[] = 'User ('.$username.') could not be created - '.$wp_user_id->get_error_message();
					} else {
						foreach (self::get_user_fields_meta() as $f) {
							$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
							if (empty($_POST[$f_post])) continue;
							$f_post1=array_map('trim',explode(',',$_POST[$f_post]));
							$f_data='';
							foreach ($f_post1 as $f_post2) {
								$f_data.= $f_data!= '' ? ' ' : '';
								$f_data.= esc_attr(apply_filters('dd_import_users_field_value_pre_import',($index = array_search($f_post2, $csv_headers))!==false ? $data[$index] : '', $f_post, $csv_headers, $data));
							}
							if (empty($f_data)) $f_data = $_POST[$f_post.'_default_value'];
							if (!isset($_POST['simulation']))
								update_user_meta($wp_user_id, $f, $f_data);
						}
						foreach (self::get_user_fields_bp() as $f) {
							$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
							if (empty($_POST[$f_post])) continue;
							$f_post1=array_map('trim',explode(',',$_POST[$f_post]));
							$f_data='';
							foreach ($f_post1 as $f_post2) {
								$f_data.= $f_data!= '' ? ' ' : '';
								$f_data.= apply_filters('dd_import_users_field_value_pre_import',($index = array_search($f_post2, $csv_headers))!==false ? $data[$index] : '', $f_post, $csv_headers, $data);
							}
							$default_value = $_POST[$f_post.'_default_value'];
							if (empty($f_data)) $f_data = $default_value;
							$f_id=str_replace('bpfield__', '', $f);
							if (($f_instance=BP_XProfile_Field::get_instance($f_id))) {
								$f_type=$f_instance->type;
								$f_name=$f_instance->name;
								//common cases characters replace
								if ($f_type!='checkbox' && $f_type!='selectbox' && $f_type!='multiselectbox' && $f_type!='radio') $f_data = str_replace("£", '&pound;', $f_data);
								if ($f_type=='datebox') {
									if (!empty($default_value)) {
										if (($strtotime=strtotime(str_replace('/', '-', $default_value)))!==false) {
											$default_value = date('Y-m-d H:i:s',$strtotime);
										} else {
											$warnings[] = 'Line '.$line.': "'.$f_name.'" has invalid date default value "'.$default_value.'". Empty default value has been used.';
											$default_value='';
										}
									}
									if (empty($f_data)) $f_data = $default_value;
									if (!empty($f_data)) {
										if (($strtotime=strtotime(str_replace('/', '-', $f_data)))!==false) {
											$f_data = date('Y-m-d H:i:s',$strtotime);
										} else {
											$warnings[] = 'Line '.$line.': User ('.$username.') has invalid date value for "'.$f_name.'" - '.$f_data.'. Default value "'.$default_value.'" has been used.';
											$f_data = $default_value;
										}
									}
								} else if ($f_type=='textbox') {
									$f_data = str_replace("\n", '. ', $f_data);
								} else if ($f_type=='checkbox') {
									$default_value=!empty($default_value) ? array_map('trim',explode(self::$global_delimiter,$default_value)) : array();
									$f_data=!empty($f_data) ? array_map('trim',explode(self::$global_delimiter,$f_data)) : $default_value;
									for ($i=0;$i<count($f_data);$i++) {
										switch(strtolower($f_data[$i])) {
											case 'true': case '1': case 'yes':
												$f_data[$i]='Yes';
											break;
										}
									}
								}
								if (!isset($_POST['simulation'])) {
									if (!xprofile_set_field_data($f_id, $wp_user_id, $f_data)) {
										$warnings[] = 'Line '.$line.': User ('.$username.') has invalid value for "'.$f_name.'" - '.(is_array($f_data) ? implode(', ',$f_data) : $f_data).'. Default value "'.(is_array($default_value) ? implode(', ',$default_value) : $default_value).'" has been used.';
										if (!xprofile_set_field_data($f_id, $wp_user_id, $default_value)) {
											$warnings[] = 'Line '.$line.': User ('.$username.') saving default value for "'.$f_name.'" - "'.(is_array($default_value) ? implode(', ',$default_value) : $default_value).'" has failed.';
										}
									}
								}
							}
						}
						foreach (self::get_user_fields_pmpro() as $f) {
							if ($f!='PmPro Level') {
								continue; //the other fields are taken into account at the point we create the membership level
							}
							$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
							if (empty($_POST[$f_post])) continue;
							if (($index = array_search($_POST[$f_post], $csv_headers))!==false) {
								$mem_level_name = apply_filters('dd_import_users_field_value_pre_import',$data[$index], $f_post, $csv_headers, $data);
								if (!empty($_POST['pmpro_level_default_value']) && (empty($mem_level_name) || !pmpro_getLevel($mem_level_name))) $mem_level_name=$_POST['pmpro_level_default_value'];
								if (($new_level = pmpro_getLevel($mem_level_name))) {
									$level = (array)$new_level;
									$new_startdate=$startdate=date('Y-m-d');
									if (!empty($_POST['membership_start_date_default_value'])) {
										$strtotime=strtotime(str_replace('/', '-', $_POST['membership_start_date_default_value']));
										if ($strtotime!==false) {
											$new_startdate=date('Y-m-d',$strtotime);
										} else {
											$warnings[] = 'Invalid default "Membership Start Date" - '.$_POST['membership_start_date_default_value'].'. Todays date has been used.';
										}
									}
									$startdate_post = str_replace('.', '_', str_replace(' ', '_', 'Membership Start Date'));
									if (($index = array_search($_POST[$startdate_post], $csv_headers))!==false) {
										$startdate = apply_filters('dd_import_users_field_value_pre_import',$data[$index], $startdate_post, $csv_headers, $data);
										if (!empty($startdate)) {
											$strtotime=strtotime(str_replace('/', '-', $startdate));
											if ($strtotime!==false) {
												$startdate = date('Y-m-d',$strtotime);
											} else {
												$warnings[] = 'Line '.$line.': User ('.$username.') has invalid "Membership Start Date" - '.$startdate.'. '.date('d/m/Y',strtotime($new_startdate)).' has been used.';
												$startdate = $new_startdate;
											}
										}
									}
									$enddate=null;
									switch ($_POST['membership_expiry_date_default_value']) {
										case 'leave_empty':
											$new_enddate = null;
										break;
										case 'expire_today':
											$new_enddate = date('Y-m-d');
										break;
										case 'membership_start_date_and_length_of_the_user_membership':
											$interval = new DateInterval('P'.$level['cycle_number']. strtoupper(substr($level['cycle_period'],0,1)));
											$d1 = new DateTime($startdate);
											$new_enddate = date_add($d1, $interval);
											$new_enddate = date('Y-m-d',$new_enddate->getTimestamp());
										break;
										case 'todays_date_and_length_of_the_user_membership':
											$new_enddate = date('Y-m-d', strtotime('+'.$level['cycle_number'].' '.strtolower($level['cycle_period'])));
										break;
									}
									$enddate_post = str_replace('.', '_', str_replace(' ', '_', 'Membership Expiry Date'));
									if (($index = array_search($_POST[$enddate_post], $csv_headers))!==false) {
										$enddate = apply_filters('dd_import_users_field_value_pre_import',$data[$index], $enddate_post, $csv_headers, $data);
										if (!empty($enddate)) {
											$strtotime=strtotime(str_replace('/', '-', $enddate));
											if ($strtotime!==false) {
												$enddate = date('Y-m-d',$strtotime);
											} else {
												$warn = 'Line '.$line.': User ('.$username.') has invalid "Membership Expiry Date" - '.$enddate.'.';
												switch ($_POST['membership_expiry_date_default_value']) {
													case 'leave_empty': $warn .= ' It has been left empty (as per the selected option).';break;
													case 'expire_today': $warn .= ' It has been expired today (as per the selected option).';break;
													case 'membership_start_date_and_length_of_the_user_membership': $warn .= ' Membership Start Date + the membership length of "'.$level['cycle_number'].' '.$level['cycle_period'].'" has been used = '.date('d/m/Y',strtotime($new_enddate)).' (as per the selected option).';break;
													case 'todays_date_and_length_of_the_user_membership': $warn .= ' Todays date + the membership length of "'.$level['cycle_number'].' '.$level['cycle_period'].'" has been used = '.date('d/m/Y',strtotime($new_enddate)).' (as per the selected option).';break;
												}
												$warnings[] = $warn;
												$enddate = $new_enddate;
											}
										}
									}
									$level['membership_id']=$new_level->id;
									$level['startdate'] = $startdate;
									$level['enddate'] = !empty($enddate) ? $enddate : $new_enddate;
									$level['user_id'] = $wp_user_id;
									$level['code_id'] = 0;
									if (!isset($_POST['simulation']))
										pmpro_changeMembershipLevel($level, $wp_user_id);
								}
							}
						}
					}
				}
				$line++;
			}
			fclose($handle);
			$log = '';
			if (!empty($errors)) {
				$log.='Errors: '.implode("\n", $errors)."\n\n";
			}
			if (!empty($warnings)) {
				$log.='Warnings: '.implode("\n", $warnings)."\n\n";
			}
			$log.=$inserted . ' users were inserted'."\n\n";
			$log.=$updated . ' users were updated'."\n\n";
			$skipped_count = count($skipped);
			if ($skipped_count>0) {
				$log.=$skipped_count . ' users were skipped:'."\n";
				for ($i=0;$i<$skipped_count;$i++) {
					$log.=$skipped[$i]."\n\n";
				}
			}
			$log_filename = ABSPATH.'wp-content/uploads/'.__CLASS__.'/'.__CLASS__.'.txt';
			$handle = @fopen($log_filename, "w") or die("Unable to open file!");
			fwrite($handle, $log);
			fclose($handle);
			$_SESSION['dd_import_users_errors']=$_SESSION['dd_import_users_warnings']=$_SESSION['dd_import_users_inserted']=$_SESSION['dd_import_users_updated']=$_SESSION['dd_import_users_skipped']=null;
			$file_url = get_site_url().'/wp-content/uploads/'.__CLASS__.'/'.__CLASS__.'.txt?nocache='.time();
			die((isset($_POST['simulation']) ?'Simulation':'Live').' import DONE. See the <a href="'.$file_url.'" target="_blank">Log</a>');
		}
	}
	public function dd_import_users_page() {
		/*if (isset($_GET['qqwxa'])) {
		    //CLEAR ALL IMPORTED USERS
			$args = array(
				'role__not_in' => array('administrator'),
			 );
			$users = get_users( $args );
			foreach ($users as $user) {
				if (!wp_delete_user($user->ID)) die('ERROR on user_id: '.$user->ID);
			}
			die('DONE');
		}*/
		date_default_timezone_set('Europe/London');
		$filename = ABSPATH.'wp-content/uploads/'.__CLASS__.'/'.__CLASS__.'.csv';
		$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 1;
		if (isset($_POST['submit']) && isset($_FILES['file']) && !empty($_FILES['file']['type']) && $step==1) {
			if (!function_exists('wp_handle_upload')) {
				require_once(ABSPATH . 'wp-admin/includes/file.php');
			}
			$uploadedfile = $_FILES['file'];
			$uploadedfile['name'] = __CLASS__.'.csv';
			$upload_overrides = array('test_form' => false, 'test_type' => false,'unique_filename_callback'=>__CLASS__.'::unique_filename_callback');
			add_filter('upload_dir', __CLASS__.'::upload_dir_temp');
			$movefile = wp_handle_upload($uploadedfile, $upload_overrides);
			if ($movefile && !isset($movefile['error'])) {
				$step+=1;
			} else {
				wp_die($movefile['error']);
			}
			remove_filter('upload_dir',  __CLASS__.'::upload_dir_temp');
		} else if (isset($_POST['submit']) && isset($_POST['select_the_previously_uploaded_file'])) {
			$step+=1;
		} else if (isset($_POST['submit']) && isset($_POST['user_login'])) {
			$step+=1;//step 2 is submitted, run step 3, it is run via ajax
		}
		global $wpdb;
		if ($step==2) {
			$handle = @fopen($filename, "r");
			if (!$handle) {
				wp_die('File ' . $filename.' not found');
			}
			if (($data = fgetcsv($handle)) !== FALSE) {
				$csv_headers = array_map('trim', $data);
			}
			$count_csv_records = 0;
			while (($data = fgetcsv($handle)) !== FALSE) {
				$count_csv_records++;
			}
		}
		?>
<table class="dd_import_users-layout dd_import_users-step-1">
	<tbody><tr>
		<td class="left">
			<div class="dd_import_users-wrapper">
				<form method="post" action="" class="dd_import_users-admin-form"<?php if ($step<2) { ?> enctype="multipart/form-data"<?php } ?> autocomplete="off">
				<div class="dd_import_users-header">
					<div class="dd_import_users-title">
						<h2>DD Users CSV Import<?=isset($count_csv_records) ? ' <i style="font-size:60%;">('.$count_csv_records.' users will be imported)</i>' : ''?><input type="hidden" name="count_csv_records" id="count_csv_records" value="<?=$count_csv_records?>"></h2>
					</div>
					<?php if ($step==2) : ?>
					<div class="dd_import_users-import-settings">
						<label>Skip Existing Users <input type="checkbox" name="skip_existing_users" id="skip_existing_users" value="1" /></label>
						<label style="padding-bottom:8px;padding-top:6px;">If required fields are missing: <select name="if_required_fields_are_missing" id="if_required_fields_are_missing" style="height:auto;width:295px;"><option value="">-- Skip users and show notice in the log --</option><option value="1">Import users and force to fill on 1st login</option><option value="2">Just Import users (i.e. required fields are handled in other part of the system)</option></select></label>
						<label>Simulation Enabled <input type="checkbox" name="simulation" id="simulation" value="1" checked="checked" /></label>
					</div>
					<div class="dd_import_users-import-settings">
						<?php
						if($wpdb->get_var("SHOW TABLES LIKE 'wp_bp_xprofile_fields'")) { ?>
						<label>For multiple checkboxes use <?=self::$global_delimiter?> to separate the values in the csv.</label>
						<?php
						}
						?>
					</div>
					<?php endif; ?>
				</div>
				<div class="dd_import_users-upload-resource-step step-<?=$step?>">
					<input type="hidden" name="step" value="<?=$step?>">
					<div class="dd_import_users-import-types">
					<?php if ($step==1) { ?>
					<input type="file" name="file" value="" style="visibility: hidden;"/>
					<div class="clear"></div>											
						<a class="dd_import_users-import-from dd_import_users-upload-type" rel="upload_type" href="javascript:void(0);">
							<span class="dd_import_users-icon"></span>
							<span class="dd_import_users-icon-label">Upload a file</span>
						</a>
						<?php
						if (file_exists($filename)) { ?>
						<br><label>or select the previously uploaded file on <?=date('d/m/Y H:i',filemtime($filename));?> <input type="checkbox" name="select_the_previously_uploaded_file" value="1" /></label>
						<?php
						} ?>
					<div id="dd_import_users-url-upload-status"></div>
					<?php } else if ($step==2) { ?>
						<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
						<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
						<div class="dd_import_users-user_fields"><strong>User Fields</strong>
							<ul>
							<?php
							$user_fields = self::get_user_fields_all();
							$user_fields_bp = self::get_user_fields_bp();
							$user_fields_required = self::get_user_fields_required();
							$user_fields_hints = self::get_user_fields_hints();
							$user_fields_default_values = self::get_user_fields_default_values();
							foreach ($user_fields as $f) {
								$f_post = str_replace('.', '_', str_replace(' ', '_', $f));
								$bp_group_name = '';
								$f_name=$f;
								if (in_array($f, $user_fields_bp)) {
									$f_id=str_replace('bpfield__', '', $f);
									if (($f_instance=BP_XProfile_Field::get_instance($f_id))) {
										$f_name=$f_instance->name;
										$field_group = xprofile_get_field_group($f_instance->group_id);
										if ($field_group) $bp_group_name = ' (BP group: '.$field_group->name.')';
									}
								}
								echo '<li style="margin-top:13px;border:solid 2px #CCC;padding:8px;">'.$f_name.$bp_group_name.(in_array($f, $user_fields_required) ? ' <span style="color:#FF0000;">*</span>' : '').'<br><input type="text" style="width:95%;" name="'.$f.'" value="'.(isset($_POST[$f_post]) ? esc_attr($_POST[$f_post]) : '').'" class="dropit" />'.(array_key_exists($f, $user_fields_hints) ? '<img src="/wp-content/plugins/dd_import_users/images/icon_information.gif" width="16" height="16" style="cursor:pointer;" title="'.esc_attr($user_fields_hints[$f]).'" />' : '').(array_key_exists($f, $user_fields_default_values) ? '<br>'.$user_fields_default_values[$f] : '').'</li>';
							} ?>
							</ul>
						</div>
						<div class="dd-import-users-sticky"><strong>CSV Fields</strong> (Drag these into the User Fields to map them)
							<ul>
							<?php foreach ($csv_headers as $f) {
								echo '<li class="csv_header_title"><a href="#">'.$f.'</a></li>';
							} ?>
							</ul>
						</div>
					<?php } ?>
					</div>
				</div>
				<?php if ($step==2) :
					$wp_user_id = get_current_user_id();
					if (!($dd_import_users_fields_profiles = get_user_meta($wp_user_id, 'dd_import_users_fields_profiles', true))) {
						$dd_import_users_fields_profiles = array();
					}
					?><br/><br/>
					Fields Profile <select name="dd_import_users_fields_profiles" id="dd_import_users_fields_profiles" class="input" style="width:250px;">
						<option value="">-- Please select --</option>
						<?php
						foreach ($dd_import_users_fields_profiles as $profile => $details) : ?>
							<option value="<?=$profile?>"><?=$profile?></option>
						<?php
						endforeach;
						?>
					</select>
					<div id="buttons-wrapper1" style="display:none;margin-top:5px;">
					<input style="margin-bottom:5px;" type="button" name="submit" class="button button-primary dd_import_users-load-profile" value="Load profile into the current fields" /><br/>
					<input style="margin-bottom:5px;" type="button" name="submit" class="button button-primary dd_import_users-update-fields-profile" value="Update profile with the current fields" /><br/>
					<input type="button" name="submit" class="button button-primary dd_import_users-delete-fields-profile" value="Delete profile" /></div><br/>
					<input style="margin-top:2px;" type="button" name="submit" class="button button-primary dd_import_users-create-fields-profile" value="Create profile with the current fields" />
				<?php endif; ?>
				<?php if ($step<3) : ?>
				<p class="dd_import_users-submit-buttons" style="display: block;">
					<?php if ($step==2) : ?>
					Process records per query <input type="number" id="process_records_per_query" value="15" style="width:75px;" /> <i>Adjust based on your server script timeout</i><br/>
					<?php endif; ?>
					<input type="submit" name="submit" class="button button-primary button-hero dd_import_users-large-button" value="<?=$step<2 ? 'Continue to Step 2' : ''?><?=$step==2 ? 'Run the import' : ''?>" /> <img id="dd_import_users_run_spinner" src="/wp-admin/images/spinner.gif" width="20" height="20" border="0" align="middle" style="display:none;" />
					<div id="progressbardd_import_users" style="display:none;"><div class="progress-labeldd_import_users progresslabel" style="position:absolute;padding-left:0px;padding-top:4px;width:91%;text-align:center;">Processing...</div></div>
				</p>
				<?php endif; ?>
				<table><tbody><tr><td class="dd_import_users-note"></td></tr></tbody></table>
				</form>
			</div>
		</td>		
	</tr>
</tbody></table>
	<?php
	}
	public function admin_notices_dd_import_users() {
		if (isset($_REQUEST['msg_dd_import_users'])) {
			$class = 'notice notice-error';
			printf('<div class="%1$s"><p>%2$s</p></div>', $class, $_REQUEST['msg_dd_import_users']);
		}
	}
	public function wp_loaded_dd_import_users() {
		add_filter('plugin_row_meta', array($this, 'plugin_row_meta_dd_import_users'), 10, 2);
	}
	public function plugin_row_meta_dd_import_users($links, $file) {
		if ($file == plugin_basename( __FILE__ )) {
			$links[] = '<a href="https://github.com/import_users" target="_blank">GitHub</a>';
		}
		return $links;
	}
	public function screen_option() {
		$option = 'per_page';
		$args   = array(
			'label'   => 'Users',
			'default' => 5,
			'option'  => 'users_per_page'
		);
		add_screen_option( $option, $args );
	}
	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

add_action('plugins_loaded', function () {
	dd_import_users::get_instance();
});