<?php
/*
Plugin Name: AShop Commerce
Plugin URI: http://www.ashopsoftware.com/wordpress
Description: Lets you use the AShop shopping cart from a WordPress blog.
Version: 1.2
Author: Andreas Rimheden
Author URI: http://www.ashopworld.net
License: GPL2

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/************************************/
/* Plugin Deactivation              */
/************************************/

function ashop_deactivate() {
  // Remove Installed Options
  delete_option("ashop_dbhost");
  delete_option("ashop_dbhost");
  delete_option("ashop_dbuser");
  delete_option("ashop_dbpass");
}
register_deactivation_hook(__FILE__, 'ashop_deactivate');

/************************************/
/* Widgets                          */
/************************************/

include_once dirname( __FILE__ ) . '/widgets.php';

$ashop_db = ashop_db_connection();

// Register widgets
add_action('widgets_init', create_function('', 'return register_widget("AShopCategoriesWidget");'));
add_action('widgets_init', create_function('', 'return register_widget("AShopShopsWidget");'));
add_action('widgets_init', create_function('', 'return register_widget("AShopTopListWidget");'));
add_action('widgets_init', create_function('', 'return register_widget("AShopLatestAdditionsWidget");'));
add_action('widgets_init', create_function('', 'return register_widget("AutoresponderWidget");'));

/************************************/
/* Utility Functions                */
/************************************/

// Get path to product images...
function ashop_getproductimages($productid,$imagenumber=0) {
	global $ashop_db;

	$ashop_path = ashop_get_preference('ashoppath', $ashop_db);

	$imageinfo = array();
	$additionalimages = 0;
	if ($imagenumber > 0) $imagenumberpath = "/$imagenumber";
	else $imagenumberpath = "";

	if (is_numeric($productid) && is_dir("$ashop_path/prodimg/$productid$imagenumberpath")) {
		$findfile = opendir("$ashop_path/prodimg/$productid$imagenumberpath");
		if ($findfile) while (false !== ($foundfile = readdir($findfile))) {
			if (strtolower(substr($foundfile,-4)) == ".gif") $imageinfo["format"] = "gif";
			if (strtolower(substr($foundfile,-4)) == ".jpg") $imageinfo["format"] = "jpg";
			if (!is_dir("$ashop_path/prodimg/$productid$imagenumberpath/$foundfile") && substr($foundfile,0,2) != "m-" && substr($foundfile,0,2) != "p-" && substr($foundfile,0,2) != "t-" && !is_dir($foundfile) && (strtolower(substr($foundfile,-4)) == ".gif" || strtolower(substr($foundfile,-4)) == ".jpg")) $imageinfo["main"] = $foundfile;
			if (substr($foundfile,0,2) == "m-" && !is_dir($foundfile)) $imageinfo["mini"] = $foundfile;
			if (substr($foundfile,0,2) == "p-" && !is_dir($foundfile)) $imageinfo["product"] = $foundfile;
			if (substr($foundfile,0,2) == "t-" && !is_dir($foundfile)) $imageinfo["thumbnail"] = $foundfile;
			if (is_dir("$ashop_path/prodimg/$productid$imagenumberpath/$foundfile") && is_numeric($foundfile)) $imageinfo["additionalimages"]++;
		}
	}

	return $imageinfo;
}

// Backwards compatibility for MySQLi PHP extension...
function ashop_mysqli_result($res,$row=0,$col=0){
	$numrows = @mysqli_num_rows($res);
	if ($numrows && $row <= ($numrows-1) && $row >=0) {
        mysqli_data_seek($res,$row);
        $resrow = mysqli_fetch_array($res);
        if (isset($resrow[$col])){
            return $resrow[$col];
        }
    }
    return false;
}

// Establish a connection to an AShop database
// AShop must be installed on the same server as WordPress
function ashop_db_connection() {
	$ashopdbhost = get_option('ashop_dbhost');
	$ashopdbname = get_option('ashop_dbname');
	$ashopdbuser = get_option('ashop_dbuser');
	$ashopdbpass = get_option('ashop_dbpass');
	$ashop_db = @mysqli_connect("$ashopdbhost", "$ashopdbuser", "$ashopdbpass", "$ashopdbname");
	return $ashop_db;
}

// Get a configuration option from AShop
function ashop_get_preference($prefname, $ashop_db) {
	$result = @mysqli_query($ashop_db, "SELECT prefvalue FROM preferences WHERE prefname='$prefname'");
	$preferencevalue = @ashop_mysqli_result($result,0,"prefvalue");
	return $preferencevalue;
}

// Get information about a product
function ashop_get_productdetails($productid, $ashop_db) {
	if (is_numeric($productid)) {
		$result = @mysqli_query($ashop_db, "SELECT * FROM product WHERE productid='$productid'");
		$row = @mysqli_fetch_array($result);
	}
	return $row;
}

// Return an array with a list of product categories available in AShop
function ashop_get_categories($ashop_db, $shop=1) {
	$categories = array();
	$result = @mysqli_query($ashop_db, "SELECT categoryid,name FROM category WHERE parentcategoryid=categoryid AND grandparentcategoryid=categoryid AND (userid='$shop'  OR memberclone='1') ORDER BY ordernumber");
	while ($row = @mysqli_fetch_array($result)) {
		$categoryid = $row["categoryid"];
		$categoryname = $row["name"];
		$categories[$categoryid] = $categoryname;
	}
	return $categories;
}

// Return an array with a list of shops available in AShop
function ashop_get_shops($ashop_db) {
	$shops[1] = __('Main Shop');
	$result = @mysqli_query($ashop_db, "SELECT userid,shopname FROM user WHERE userid!='1'");
	while ($row = @mysqli_fetch_array($result)) {
		$shop = $row["userid"];
		$shopname = $row["shopname"];
		$shops[$shop] = $shopname;
	}
	return $shops;
}

// Return an array with a list of autoresponders available in AShop
function ashop_get_autoresponders($ashop_db) {
	$result = @mysqli_query($ashop_db, "SELECT responderid,name FROM autoresponders");
	while ($row = @mysqli_fetch_array($result)) {
		$autoresponder = $row["responderid"];
		$name = $row["name"];
		$autoresponders[$autoresponder] = $name;
	}
	return $autoresponders;
}

// Get the profile ID of a specified autoresponder
function ashop_get_arprofileid($ashop_db, $autoresponderid) {
	if (!empty($autoresponderid) && is_numeric($autoresponderid)) {
		$result = @mysqli_query($ashop_db, "SELECT profileid FROM autoresponders WHERE responderid='$autoresponderid'");
		$arprofileid = @ashop_mysqli_result($result,0,"profileid");
		return $arprofileid;
	} else return FALSE;
}

/************************************/
/* Shortcodes                       */
/************************************/

include_once dirname( __FILE__ ) . '/shortcodes.php';

/************************************/
/* Administration                   */
/************************************/

// Admin configuration options...
if ( is_admin() ) add_action('admin_menu', 'ashop_menu');
if ( is_admin() ) add_action('admin_menu', 'ashop_quicklink');

add_option('ashop_dbhost');
add_option('ashop_dbname');
add_option('ashop_dbuser');
add_option('ashop_dbpass');

function ashop_menu() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('AShop Configuration'), __('AShop Configuration'), 'manage_options', 'ashop-config', 'ashop_conf');
}

function ashop_conf() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	if ( isset($_POST['submit']) ) {
		$ashop_dbhost = $_POST["ashopdbhost"];
		$ashop_dbname = $_POST["ashopdbname"];
		$ashop_dbuser = $_POST["ashopdbuser"];
		$ashop_dbpass = $_POST["ashopdbpass"];
		$ashop_db = @mysqli_connect("$ashop_dbhost", "$ashop_dbuser", "$ashop_dbpass", "$ashop_dbname");
		@mysql_select_db("$ashop_dbname",$ashop_db);
		if (@mysqli_error()) {
			$ashop_message = '<p style="width: 500px; padding: .5em; background-color: #B94A15; color: #fff; font-weight: bold;">';
			$ashop_message .= __('Unable to connect. The login options you entered are probably incorrect.');
			$ashop_message .= '</p>';
		} else {
			update_option( 'ashop_dbhost', $ashop_dbhost );
			update_option( 'ashop_dbname', $ashop_dbname );
			update_option( 'ashop_dbuser', $ashop_dbuser );
			update_option( 'ashop_dbpass', $ashop_dbpass );
			$wordpresspath = ABSPATH;
			if (substr($wordpresspath,-1) == "/") $wordpresspath = substr($wordpresspath,0,-1);
			$wordpresspath = @mysqli_real_escape_string($wordpresspath,$ashop_db);
			$result = @mysqli_query($ashop_db, "SELECT * FROM preferences WHERE prefname='wordpresspath'");
			if (!@mysqli_num_rows($result)) @mysqli_query($ashop_db, "INSERT INTO preferences (prefid, prefname, prefvalue) VALUES ('172', 'wordpresspath', '$wordpresspath')");
			@mysqli_query($ashop_db, "UPDATE preferences SET prefvalue='$wordpresspath' WHERE prefname='wordpresspath'");
			$ashop_message = '<p style="width: 500px; padding: .5em; background-color: #4AB915; color: #fff; font-weight: bold;">';
			$ashop_message .= __('Options saved.');
			$ashop_message .= '</p>';
		}
	}
	echo '<div class="wrap">';
	echo '<h2>';
	_e('AShop Configuration');
	echo '</h2>';
	if (!empty($ashop_message)) echo $ashop_message;
	echo '<p>';
	_e('Enter the connection parameters for your AShop database.');
	echo '</p>';
	echo '<form action="" method="post">';
	echo '<p>';
	_e('Database host: ');
	echo '<input id="ashopdbhost" name="ashopdbhost" type="text" size="35" value="';
	$ashop_dbhost = get_option( 'ashop_dbhost' );
	if (empty($ashop_dbhost)) $ashop_dbhost = 'localhost';
	echo $ashop_dbhost;
	echo '"/></p>';
	echo '<p>';
	_e('Database name: ');
	echo '<input id="ashopdbname" name="ashopdbname" type="text" size="35" value="';
	$ashop_dbname = get_option( 'ashop_dbname' );
	echo $ashop_dbname;
	echo '"/></p>';
	echo '<p>';
	_e('Database user: ');
	echo '<input id="ashopdbuser" name="ashopdbuser" type="text" size="35" value="';
	$ashop_dbuser = get_option( 'ashop_dbuser' );
	echo $ashop_dbuser;
	echo '"/></p>';
	echo '<p>';
	_e('Database password: ');
	echo '<input id="ashopdbpass" name="ashopdbpass" type="text" size="35" value="';
	$ashop_dbpass = get_option( 'ashop_dbpass' );
	echo $ashop_dbpass;
	echo '"/></p>';
	echo '<p class="submit"><input type="submit" name="submit" value="';
	_e('Update options &raquo;');
	echo '" /></p>';
	echo '<h2>';
	_e('Available shortcodes:');
	echo '</h2>';
	echo '<p><b>';
	_e('[ashopcategory id=nn shop=nn startitem=nn items=nn]');
	echo '</b>';
	_e(' - Show "items" number of products from category "id" in shop "shop", starting at item number "startitem".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopcategoryinfo id=nn show=name|description]');
	echo '</b>';
	_e(' - Show "name" or "description" of category "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproductname id=nn]');
	echo '</b>';
	_e(' - Show the name of product "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproductdescription id=nn]');
	echo '</b>';
	_e(' - Show the description of product "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproductprice id=nn]');
	echo '</b>';
	_e(' - Show the retail price of product "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopwholesaleprice id=nn]');
	echo '</b>';
	_e(' - Show the wholesale price of product "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproductlink id=nn]');
	echo '</b>';
	_e(' - Generate a link to the details page of product "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopbuylink id=nn attribute=nn:nn button=button.gif]');
	echo '</b>';
	_e(' - Generate a Buy Now link to product "id" with optional predefined attributes and buy button image.');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproducturl id=nn]');
	echo '</b>';
	_e(' - Show the URL to the details page of product "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproductsku id=nn attribute=nn:nn]');
	echo '</b>';
	_e(' - Show the SKU code of product "id" with the attribute "attribute".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproductimage id=nn size=thumbnail|normal|large]');
	echo '</b>';
	_e(' - Show an image of the type "size" (default: thumbnail) for product "id".');
	echo '</p>';
	echo '<p><b>';
	_e('[ashopproductinventory id=nn attribute=nn:nn]');
	echo '</b>';
	_e(' - Show the number of items in stock for product "id" with the attribute "attribute".');
	echo '</p>';
	echo '</div>';
}

// Automatic login link to the AShop admin panel
// Only available to administrators
function ashop_quicklink() {
	global $ashop_db;
	$ashop_name = ashop_get_preference('ashopname', $ashop_db);
	$page_title = $ashop_name.__('  Administration Panel');
	if ( function_exists('add_menu_page') )
		add_menu_page( $ashop_name, $ashop_name, 'manage_options', 'ashop-quicklink', 'ashop_qlink', WP_PLUGIN_URL.'/ashop-commerce/adminicon.gif' );
}

function ashop_qlink() {
	global $ashop_db, $current_user;
	$ashop_url = ashop_get_preference('ashopurl', $ashop_db);
	get_currentuserinfo();
	$userid = $current_user->ID;
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$ashoppassword = esc_attr( get_the_author_meta( 'ashoppassword', $userid ) );
	$result = @mysqli_query($ashop_db, "SELECT licensekey FROM user WHERE userid='1'");
	$licensekey = @ashop_mysqli_result($result,0,"licensekey");
	$key = md5($licensekey);
	$decryptedpassword = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($ashoppassword), MCRYPT_MODE_CBC, md5(md5($key))), "\0");

	echo "<form method=\"post\" action=\"$ashop_url/admin/login.php\" name=\"ashop_form\" target=\"_blank\">
	<input type=\"hidden\" name=\"username\" value=\"";
	echo esc_attr( get_the_author_meta( 'ashopuser', $userid ) );
	echo "\" />
	<input type=\"hidden\" name=\"password\" value=\"$decryptedpassword\" />
	<input type=\"hidden\" name=\"override\" value=\"true\">
	</form>
	<script language=\"JavaScript\" type=\"text/javascript\">document.ashop_form.submit();history.back();</script>";
}

// Let the user store their AShop admin panel login details for use with the quick link
add_action( 'show_user_profile', 'ashop_user_profile_fields' );
add_action( 'edit_user_profile', 'ashop_user_profile_fields' );
 
function ashop_user_profile_fields( $user ) {
	global $ashop_db;
	if (current_user_can('manage_options'))  {
		$ashoppassword = esc_attr( get_the_author_meta( 'ashoppassword', $user->ID ) );
		if (!empty($ashoppassword)) {
			$result = @mysqli_query($ashop_db, "SELECT licensekey FROM user WHERE userid='1'");
			$licensekey = @ashop_mysqli_result($result,0,"licensekey");
			$key = md5($licensekey);
			$decryptedpassword = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($ashoppassword), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
		}
		echo "<h3>".__("AShop admin panel login")."</h3>
		<table class=\"form-table\">
		<tr>
		<th><label for=\"ashopuser\">".__("Username")."</label></th>
		<td>
		<input type=\"text\" name=\"ashopuser\" id=\"ashopuser\" value=\"";
		echo esc_attr( get_the_author_meta( 'ashopuser', $user->ID ) );
		echo "\" class=\"regular-text\" /><br />
		<span class=\"description\">".__("Enter your AShop admin panel username.")."</span>
		</td>
		</tr>
		<tr>
		<th><label for=\"ashoppassword\">".__("Password")."</label></th>
		<td>
		<input type=\"password\" name=\"ashoppassword\" id=\"ashoppassword\" value=\"$decryptedpassword\" class=\"regular-text\" /><br />
		<span class=\"description\">".__("Enter your AShop admin panel password.")."</span>
		</td>
		</tr>
		</table>";
	}
}
 
add_action( 'personal_options_update', 'save_ashop_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_ashop_user_profile_fields' );
 
function save_ashop_user_profile_fields( $user_id ) {
	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
	global $ashop_db;
	$result = @mysqli_query($ashop_db, "SELECT licensekey FROM user WHERE userid='1'");
	$licensekey = @ashop_mysqli_result($result,0,"licensekey");
	$key = md5($licensekey);
	$encryptedpassword = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $_POST['ashoppassword'], MCRYPT_MODE_CBC, md5(md5($key))));
	update_usermeta( $user_id, 'ashopuser', $_POST['ashopuser'] );
	update_usermeta( $user_id, 'ashoppassword', $encryptedpassword );
}

/************************************/
/* Layout and Themes                */
/************************************/

// Include the plugin CSS for layout of the product list shortcode
function ashop_stylesheet() {
	$ashop_styleurl = WP_PLUGIN_URL . '/ashop-commerce/ashop.css';
	$ashop_stylefile = WP_PLUGIN_DIR . '/ashop-commerce/ashop.css';
	if ( file_exists($ashop_stylefile) ) {
		wp_register_style('AShopStyleSheets', $ashop_styleurl);
		wp_enqueue_style( 'AShopStyleSheets');
	}
}

add_action('wp_print_styles', 'ashop_stylesheet');

// Used for extracting and using a WordPress theme in AShop. Work in progress...
function ashop_header( $name = null )  {
	if (isset($_GET["ashopheader"]) && $_GET["ashopheader"] == "1") {
		do_action( 'get_header', $name );
		$templates = array();
		$templates[] = 'header.php';
		// Backward compat code will be removed in a future release
		if ('' == locate_template($templates, true))
			load_template( ABSPATH . WPINC . '/theme-compat/header.php');
		echo "<!-- AShopstart -->";
		exit;
	}
}

add_action('loop_start', 'ashop_header', 10);

function ashop_footer()  {
	if (isset($_GET["ashopfooter"]) && $_GET["ashopfooter"] == "1") echo "<!-- AShopend -->";
}

add_action('get_footer', 'ashop_footer', 10);

/************************************/
/* User Registration                */
/************************************/

// Cross registration with WordPress and AShop
// When a user registers with WordPress they are automatically signed up
// as a customer in AShop with the same password.
function ashop_register_new_wp_user($user_login, $user_email, $errors) {
	global $ashop_db;
  $errors = apply_filters('registration_errors', $errors);
  
  if(!$errors->get_error_code()) {
    // Create New User
    $user_pass = wp_generate_password();
    $user_id = wp_create_user( $user_login, $user_pass, $user_email );
    if ( !$user_id ) {
	  $errors->add('registerfail', sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !'), get_option('admin_email')));
	  return $errors;
    }

    // Register this user with AShop...
	@mysqli_query($ashop_db, "INSERT INTO customer (email,username,password) VALUES ('$user_email','$user_email','$user_pass')");
    wp_new_user_notification($user_id, $user_pass);

    // Send User Registration Email
    wp_new_user_notification($user_id, $user_pass);

    // Fake Error to Cease Normal WordPress Registration
    $errors->add('ashop_register_complete', __('Registration complete! Check your email for your password.'));
	return $errors;
  }
}

add_action('register_post', 'ashop_register_new_wp_user', 10, 3);

function ashop_register_new_bp_user() {
  global $bp, $ashop_db;

  // Check for User Registration Completion
  if ($bp->signup->step == "completed-confirmation") {
	  $user_login = $_POST['signup_username'];
	  $user_email = $_POST['signup_email'];
	  $user_pass = $_POST['signup_password']; 
  }

    // Register this user with AShop...
	@mysqli_query($ashop_db, "INSERT INTO customer (email,username,password) VALUES ('$user_email','$user_email','$user_pass')");
    wp_new_user_notification($user_id, $user_pass);
  
}

// Check for BuddyPress before Initiating Hook
if(function_exists("bp_core_check_installed")) {
  add_action('bp_complete_signup', 'ashop_register_new_bp_user', 10);
}

function ashop_user_profile_update($user_id) {
	global $ashop_db;
	$userdata = get_userdata($user_id);
	$firstname = @mysqli_real_escape_string($userdata->user_firstname,$ashop_db);
	$lastname = @mysqli_real_escape_string($userdata->user_lastname,$ashop_db);
	$email = @mysqli_real_escape_string($userdata->user_email,$ashop_db);
	if (!empty($_POST["pass1"]) && $_POST["pass1"] == $_POST["pass2"]) {
		$password = @mysqli_real_escape_string($_POST["pass1"],$ashop_db);
		@mysqli_query($ashop_db, "UPDATE customer SET firstname='$firstname',lastname='$lastname',password='$password' WHERE email='$email' OR username='$email'");
	} else @mysqli_query($ashop_db, "UPDATE customer SET firstname='$firstname',lastname='$lastname' WHERE email='$email' OR username='$email'");
}
add_action('profile_update','ashop_user_profile_update');
?>