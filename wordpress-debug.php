<?php
/*
Plugin Name: WordPress Debug
Plugin URI: http://www.semperfiwebdesign.com/wordpress-debug
Description: WordPress Debug Plugin
Author: Michael Torbert
Version: 0.2.4
Author URI: http://www.semperfiwebdesign.com/
License: GPL3
*/

add_action( 'admin_menu', 'sfwd_debug_menu_setup' );



// Add settings link on plugin page (maybe this should be in tools instead of settings?)
function wpdebugsettingslink($links) { 
  $settings_link = '<a href="options-general.php?page=wordpress-debug/wordpress-debug.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'wpdebugsettingslink' );
// End add settings link on plugin page


function sfwd_debug_menu_setup() {
	$plugin_page = add_options_page( 'WordPress Debug', 'WordPress Debug', 'manage_options', __FILE__, 'sfwd_debug_get_serverinfo' );
  	add_action( 'admin_head-'. $plugin_page, 'sfwd_debug_admin_header' );
}

function sfwd_debug_admin_header() {
?>
<style>
ul.sfwd_debug_settings li strong {
	min-width: 33%;
	display: block;
	float: left;
	text-align: right;
	background-color: #DDD;
	margin-right: 8px;
	padding: 1px;
	padding-right: 8px;
}
ul.sfwd_debug_settings li {
	clear: left;
	padding: 5px;
	margin: 0px;
	padding: 0px;
	background-color: #EEE;
	max-width: 80%;
	min-width: 600px;
}
div.sfwd_debug_mail_sent {
	background-color: #080;
	border: 1px solid #0A0;
	margin: 10px 0px 10px 0px;
	width: 598px;
	color: #FFF;
	text-align: center;
}
div.sfwd_debug_error {
	background-color: #F00;
	color: #FFF;
	border: 1px solid #A00;
	margin: 10px 0px 10px 0px;
	width: 598px;
	text-align: center;
	font-weight: bolder;
}
</style>
<?php
}

function sfwd_debug_get_serverinfo() {
        global $wpdb;
	global $wp_version;

        $sqlversion = $wpdb->get_var("SELECT VERSION() AS version");
        $mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
        if (is_array($mysqlinfo)) $sql_mode = $mysqlinfo[0]->Value;
        if (empty($sql_mode)) $sql_mode = __('Not set');
        if(ini_get('safe_mode')) $safe_mode = __('On');
        else $safe_mode = __('Off');
        if(ini_get('allow_url_fopen')) $allow_url_fopen = __('On');
        else $allow_url_fopen = __('Off');
        if(ini_get('upload_max_filesize')) $upload_max = ini_get('upload_max_filesize');
        else $upload_max = __('N/A');
        if(ini_get('post_max_size')) $post_max = ini_get('post_max_size');
        else $post_max = __('N/A');
        if(ini_get('max_execution_time')) $max_execute = ini_get('max_execution_time');
        else $max_execute = __('N/A');
        if(ini_get('memory_limit')) $memory_limit = ini_get('memory_limit');
        else $memory_limit = __('N/A');
        if (function_exists('memory_get_usage')) $memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MByte');
        else $memory_usage = __('N/A');
        if (is_callable('exif_read_data')) $exif = __('Yes'). " ( V" . substr(phpversion('exif'),0,4) . ")" ;
        else $exif = __('No');
        if (is_callable('iptcparse')) $iptc = __('Yes');
        else $iptc = __('No');
        if (is_callable('xml_parser_create')) $xml = __('Yes');
        else $xml = __('No');

	if ( function_exists( 'wp_get_theme' ) ) {
		$theme = wp_get_theme();
	} else {
		$theme = get_theme( get_current_theme() );
	}

	if ( function_exists( 'is_multisite' ) ) {
		if ( is_multisite() ) {
			$ms = __('Yes');
		} else {
			$ms = __('No');
		}
		 
	} else $ms = __('N/A');

	$siteurl = get_option('siteurl');
	$homeurl = get_option('home');
	$db_version = get_option('db_version');

	$debug_info = Array(
	        __('Operating System')			=> PHP_OS,
	        __('Server')				=> $_SERVER["SERVER_SOFTWARE"],
	        __('Memory usage')			=> $memory_usage,
	        __('MYSQL Version')			=> $sqlversion,
	        __('SQL Mode')				=> $sql_mode,
	        __('PHP Version')			=> PHP_VERSION,
	        __('PHP Safe Mode')			=> $safe_mode,
	        __('PHP Allow URL fopen')		=> $allow_url_fopen,
	        __('PHP Memory Limit')			=> $memory_limit,
	        __('PHP Max Upload Size')		=> $upload_max,
	        __('PHP Max Post Size')			=> $post_max,
	        __('PHP Max Script Execute Time')	=> $max_execute,
	        __('PHP Exif support')			=> $exif,
	        __('PHP IPTC support')			=> $iptc,
	        __('PHP XML support')			=> $xml,
		__('Site URL')				=> $siteurl,
		__('Home URL')				=> $homeurl,
		__('WordPress Version')			=> $wp_version,
		__('WordPress DB Version')		=> $db_version,
		__('Multisite')				=> $ms,
		__('Active Theme')			=> $theme['Name'].' '.$theme['Version']
	);
	$debug_info['Active Plugins'] = null;
	$active_plugins = $inactive_plugins = Array();
	$plugins = get_plugins();
	foreach ($plugins as $path => $plugin) {
		if ( is_plugin_active( $path ) ) {
			$debug_info[$plugin['Name']] = $plugin['Version'];
		} else {
			$inactive_plugins[$plugin['Name']] = $plugin['Version'];
		}
	}
	$debug_info['Inactive Plugins'] = null;
	$debug_info = array_merge( $debug_info, (array)$inactive_plugins );
?>
	<h1>WordPress Debug</h1>
<?php
	$mail_text = "WordPress Debug\r\n----------\r\n\r\n";
	$page_text = "";
	foreach($debug_info as $name => $value) {
		if ($value !== null) {
			$page_text .= "<li><strong>$name</strong> $value</li>";
			$mail_text .= "$name: $value\r\n";
		} else {
			$page_text .= "</ul><h2>$name</h2><ul class='sfwd_debug_settings'>";
			$mail_text .= "\r\n$name\r\n----------\r\n";
		}
	}

	do if ( !empty( $_REQUEST['sfwd_debug_submit'] ) ) {
		$nonce=$_REQUEST['sfwd_debug_nonce'];
		if (! wp_verify_nonce($nonce, 'sfwd-debug-nonce') ) {
				echo "<div class='sfwd_debug_error'>Form submission error: verification check failed.</div>";
				break;
		}
		if ($_REQUEST['sfwd_debug_send_email']) {
			if (wp_mail($_REQUEST['sfwd_debug_send_email'], "WordPress Debug Mail From Site $siteurl", $mail_text)) {
				echo "<div class='sfwd_debug_mail_sent'>Sent to " . $_REQUEST['sfwd_debug_send_email'] . "</div>";
			} else {
				echo "<div class='sfwd_debug_error'>Failed to send to " . $_REQUEST['sfwd_debug_send_email'] . "</div>";
			}
		} else {
			echo "<div class='sfwd_debug_error'>Error: please enter an e-mail address before submitting.</div>";
		}
	} while(0); // control structure for use with break
?>
	<ul class='sfwd_debug_settings'>
	<?php echo $page_text; ?>
	</ul>
	<p>
<?php
	$nonce = wp_create_nonce('sfwd-debug-nonce');
?>
	<form method="post" action="">
		E-mail this debug information to:
		<input name="sfwd_debug_send_email" type="text"    value="">
		<input name="sfwd_debug_nonce"	    type="hidden"  value="<?php echo $nonce; ?>">
		<input name="sfwd_debug_submit"      type="submit" value="Submit">
	</form>
<?php
}
?>
