<?php
/*
Plugin Name: Invoice Sync for Xero and WP eCommerce
Plugin URI: 
Description: Plugin to add xero invoice in WP eCommerce
Version: 1.0.0
Author: Vbridge
*/

/* --- Static initializer for Wordpress hooks --- */
define ( "XRO_APP_TYPE", "Private" );
define ( "OAUTH_CALLBACK", "oob" );

add_action('admin_menu', 'isxwpe_xero_create_menu');
function isxwpe_xero_create_menu() // Function to create left menu on wp-admin
{
	if (function_exists('add_menu_page')) {
		
	  add_menu_page('Page title', 'Xero Auth', 'manage_options', 'invoice-sync-for-xero-and-wpecommerce/xero-invoice.php', 'isxwpe_xero_settings_menu');
	  add_submenu_page( 'invoice-sync-for-xero-and-wpecommerce/xero-invoice.php', 'Page title', 'Xero Sync History', 'manage_options', 'invoice-sync-for-xero-and-wpecommerce/isxwpe_invoice_history.php', 'isxwpe_invoice_history');
	  add_submenu_page( 'invoice-sync-for-xero-and-wpecommerce/xero-invoice.php', 'Page title', 'Help', 'manage_options', 'invoice-sync-for-xero-and-wpecommerce/isxwpe_plugin_help.php', 'isxwpe_plugin_help');
	}
	  
}

function isxwpe_invoice_history() // function to display invoice history
{
	global $wpdb; 
	//plugins_url(). '/invoice-sync-for-xero-and-wpecommerce/css/jquery-ui.css'
	//wp_enqueue_style( 'date_picker-style', plugins_url(). '/invoice-sync-for-xero-and-wpecommerce/css/jquery-ui.css' ); // add datepicker style
	wp_enqueue_style( 'date_picker-style', plugins_url( '/css/jquery-ui.css', __FILE__ ));
	wp_enqueue_script('jquery-ui-datepicker'); // add datepicker jquery files
	?>
	<script>
	jQuery(document).ready(function(jQuery) {
		jQuery('#start_date').datepicker({
			dateFormat: 'yy-mm-dd'
		});
		jQuery('#end_date').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	});
	</script>
	<?php $end_date = date('Y-m-d');
	$upload_dir = wp_upload_dir();
	$current_url = admin_url().'admin.php?page=invoice-sync-for-xero-and-wpecommerce/isxwpe_invoice_history.php'; // Select base url of history page
	$start_date = date('Y-m-d' ,strtotime('-1 month'));
	if(isset($_GET['start_date']) && $_GET['start_date'] != '')
	{
		$start_date = date('Y-m-d' ,strtotime($_GET['start_date'])); // select start date from date posted on calender
		$current_url = $current_url.'&start_date='.$_GET['start_date']; // Set selected start date for pagination
		
	}
	if(isset($_GET['end_date']) && $_GET['end_date'] != '')
	{
		$end_date = date('Y-m-d' ,strtotime($_GET['end_date'])); //select end date from date posted on calender
		$current_url = $current_url.'&end_date='.$_GET['end_date']; // Set selected end date for pagination
	}
	$end_date_for_sel = date('Y-m-d', strtotime($end_date. ' + 1 days'));
	//echo "SELECT * FROM wp_xero_history WHERE `purchased_date` >= '$start_date' AND `purchased_date` <= '$end_date_for_sel' ORDER BY id DESC";
	?>
	<h3>Invoice Sync History</h3>
	<p style="font-size: 16px;">All the invoices that were pushed to Xero are listed below</p>
	<form action="<?php echo get_site_url().'/wp-admin/admin.php'; ?>" method="get" class="history_form">
		<input type="hidden" name="page" value="invoice-sync-for-xero-and-wpecommerce/isxwpe_invoice_history.php" />
		<b style="font-size:15px">Shows results from</b>
		<input type="text" id="start_date" value = <?php echo $start_date; ?> name="start_date" placeholder="Start date">
		<b style="font-size:15px">To</b>
		<input type="text" id="end_date" value = <?php echo $end_date; ?> name="end_date" placeholder="End date">
		<input type="submit" name="filter_form" value="Filter results">
	</form>
	<?php
	$max = 10;
	/*Get the current page eg index.php$current_url&pg=4*/
	
	if(isset($_GET['pg'])){
		$page= $_GET['pg'];
	}else{
		$page= 1;
	}

	$limit = ($page- 1) * $max;
	$prev = $page- 1;
	$next = $page+ 1;
	$limits = (int)($page- 1) * $max;


	//This is the query to get the current dataset
	$result_query = "SELECT * FROM $wpdb->prefix"."xero_history WHERE `purchased_date` >= '$start_date' AND `purchased_date` <= '$end_date_for_sel' ORDER BY id DESC limit $limits,$max";
	

	//Get total records from db
	$totalresults = $wpdb->get_results( "SELECT COUNT(id) AS tot FROM $wpdb->prefix"."xero_history WHERE `purchased_date` >= '$start_date' AND `purchased_date` <= '$end_date_for_sel'" );
	$totalres = $totalresults[0]->tot;
	$totalposts = ceil($totalres / $max);
	$lpm1 = $totalposts - 1;
	$results = $wpdb->get_results( $result_query );
	
	if(!empty($results))
	{
	echo isxwpe_pagination($totalposts,$p,$lpm1,$prev,$next,$current_url);
   ?>
   <style>
   .invoice_list td{
   border : 1px solid;
   font-size:16px;
   padding:10px;
   }
   .history_form{
   border: 3px solid skyblue;
    border-radius: 10px;
    margin: auto auto 10px 0;
    padding-bottom: 5px;
    padding-top: 5px;
    text-align: center;
    width: 73%;
   }
   .pagination_main {
   width: 73%;
   float:left;
   margin-bottom: 5px;
   margin-top: 5px;
   }
   .pagination_main a {
   font-size: 16px;    
    padding: 3px;
    text-decoration: none;
   }
   </style>
	<table class="invoice_list">
	<tr >
		<td>Invoice Id</td>
		<td>Date</td>
		<td>Session Id</td>	
		<td>User Email</td>
		<td>Purchase Id</td>
		<td>Product</td>
		<td>Price</td>
		<td>Qty</td>
		<td>Total</td>
	</tr>
	
	
	
	
   <?php
   // Display history
   foreach($results as $result)
		{ 
			$purchase_id = $result->purchase_id;
			$purchased_items = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."wpsc_cart_contents WHERE purchaseid = $purchase_id " ); // Collect product details
			$wp_wpsc_purchase_logs = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."wpsc_purchase_logs WHERE id = $purchase_id " ); // Collect purchase details
			$wp_wpsc_purchase_log = $wp_wpsc_purchase_logs[0];
			foreach($purchased_items as $selected_items)
			{
				//print_r($purchased_items);
			?>
				<tr>
					<td><?php echo $result->invoice_id;?></td>
					<td><?php echo $result->purchased_date;?></td>
					<td><?php echo $result->session_id;?></td>
					<td><?php echo $result->user_email;?></td>
					<td><?php echo $result->purchase_id;?></td>
					<td><?php echo $selected_items->name;?></td>
					<td><?php echo $selected_items->price;?></td>
					<td><?php echo $selected_items->quantity;?></td>
					<td><?php echo $wp_wpsc_purchase_log->totalprice;?></td>
				</tr>
			<?php
			}
		}
		echo '</table>';
		echo isxwpe_pagination($totalposts,$p,$lpm1,$prev,$next,$current_url);
	}
	else echo '<div style=" color: red;
    font-size: 15px;
    font-weight: bold;
    margin: auto;
    padding-top: 30px;
    width: 50%;">No results found</div>';
	
}


function isxwpe_xero_create_table()
{
	// Function which create all required tables when plugin installed
    // do NOT forget this global
	global $wpdb;
 
	// this if statement makes sure that the table doe not exist already
	if($wpdb->get_var("show tables like $wpdb->prefix"."xero_auth") != "$wpdb->prefix"."xero_auth")  // Table to store xero auth details
	{
		$sql = "CREATE TABLE $wpdb->prefix"."xero_auth (
		id mediumint(10) NOT NULL AUTO_INCREMENT,
		credential varchar(100) NOT NULL,
		value tinytext NOT NULL,
		UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	if($wpdb->get_var("show tables like $wpdb->prefix"."xero_history") != "$wpdb->prefix"."xero_history")  // Table to store xero sybc history
	{
		$sql = "CREATE TABLE `$wpdb->prefix"."xero_history` (
		`id` int(50) NOT NULL AUTO_INCREMENT, 
		`session_id` int(11) NOT NULL,
		`purchase_id` int(11) NOT NULL,
		`invoice_id` varchar(30) NOT NULL,
		`purchased_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`user_email` varchar(100) NOT NULL,
		PRIMARY KEY (`id`)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}
// this hook will cause our creation function to run when the plugin is activated
register_activation_hook( __FILE__, 'isxwpe_xero_create_table' );
function isxwpe_xero_settings_menu()
{
	//Function to collect api credentials and authenticate
	global $wpdb;
	echo '<h3>Xero Authentication</h3>';
	$useragent = "XeroOAuth-PHP Private App Test";
	$application_key = $secret_key = '';
	$redirect_url = admin_url().'admin.php?page=invoice-sync-for-xero-and-wpecommerce/xero-invoice.php';
	$target_directorys = wp_upload_dir();
	$target_directory = $target_directorys['basedir'];
	if(isset($_POST['save_data']))
	{
		$application_key = $_POST['application_key'];
		$secret_key = $_POST['secret_key'];
		//$redirect_url = $_POST['redirect_url'];
		
		//print_r($target_directory);
		if (!file_exists($target_directory.'/xero_invoice/private_keys')) { 
			mkdir($target_directory.'/xero_invoice/', 0777, true);
			mkdir($target_directory.'/xero_invoice/private_keys', 0777, true);
		}
		copy(plugin_dir_path( __FILE__ ).'xero_library/certs/publickey.cer', $target_directory.'/xero_invoice/private_keys/publickey.cer');
		$target_dir = $target_directory.'/xero_invoice/private_keys/';
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		move_uploaded_file($_FILES["fileToUpload"]["tmp_name"],$target_file);
		$results = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."xero_auth" );
		if(!empty($results))
		{
			// delete old auth details from DB
			$wpdb->delete( "$wpdb->prefix"."xero_auth", array( 'credential' => 'application_key' ) );
			$wpdb->delete( "$wpdb->prefix"."xero_auth", array( 'credential' => 'secret_key' ) );
			
		}
		// Save new auth credentials to db 
		$wpdb->insert( 
			"$wpdb->prefix"."xero_auth", 
			array( 
				'credential' => 'application_key', 
				'value' => $application_key 
			), 
			array( 
				'%s', 
				'%s' 
			) 
		);
		$wpdb->insert( 
			"$wpdb->prefix"."xero_auth", 
			array( 
				'credential' => 'secret_key', 
				'value' => $secret_key 
			), 
			array( 
				'%s', 
				'%s' 
			) 
		);
		?>
		<style>
		   .xero_input_form {
		   display:none;
		   }
		</style>
		<?php
		$_SESSION ['auth_button_clicked'] = 1;		
	}
	$results = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."xero_auth" );
	if(!empty($results))
	{
		foreach($results as $result)
		{
			if($result->credential == 'application_key') $application_key = $result->value;
			if($result->credential == 'secret_key') $secret_key = $result->value;
			if($result->credential == 'redirect_url') $redirect_url = $result->value;
		}
	}
	if($application_key != '' && $secret_key != '' && $redirect_url != '' && isset($_SESSION ['auth_button_clicked']) && $_SESSION ['auth_button_clicked'] == 1)
	{
		
		//include WP_PLUGIN_DIR."/invoice-sync-for-xero-and-wpecommerce/xero_library/tests/testRunner.php";
		//echo plugin_dir_path( __FILE__ ).'xero_library/lib/XeroOAuth.php';
		//exit;
		require plugin_dir_path( __FILE__ ).'xero_library/lib/XeroOAuth.php';
		$signatures = array (
		'consumer_key' => $application_key,
		'shared_secret' => $secret_key,
		// API versions
		'core_version' => '2.0',
		'payroll_version' => '1.0',
		'file_version' => '1.0' 
		);
		if (XRO_APP_TYPE == "Private" || XRO_APP_TYPE == "Partner") {
			$signatures ['rsa_private_key'] = $target_directory.'/xero_invoice/private_keys/privatekey.pem';
			$signatures ['rsa_public_key'] = $target_directory.'/xero_invoice/private_keys/publickey.cer';
		}
		
		$XeroOAuth = new XeroOAuth ( array_merge ( array (
		'application_type' => XRO_APP_TYPE,
		'oauth_callback' => OAUTH_CALLBACK,
		'user_agent' => $useragent 
		), $signatures ) );
		include plugin_dir_path( __FILE__ ).'xero_library/tests/testRunner.php';
		$suceess_message = '';
		$initialCheck = $XeroOAuth->diagnostics ();
		$checkErrors = count ( $initialCheck );
		if ($checkErrors > 0) {
			echo '<div class="error_msg" style="color:red;">';
		// you could handle any config errors here, or keep on truckin if you like to live dangerously
			foreach ( $initialCheck as $check ) {
				echo 'Error: ' . $check . PHP_EOL;
			}
			echo '</div>';
		} 
		else {
			
			$session = persistSession ( array (
			'oauth_token' => $XeroOAuth->config ['consumer_key'],
			'oauth_token_secret' => $XeroOAuth->config ['shared_secret'],
			'oauth_session_handle' => '' 
			) );
			$oauthSession = retrieveSession ();
			if (isset ( $oauthSession ['oauth_token'] )) {
				$suceess_message = '<div class="success_message" style="color:green; font-size:15px;">Authenticated Successfully</div>';
				$XeroOAuth->config ['access_token'] = $oauthSession ['oauth_token'];
				$XeroOAuth->config ['access_token_secret'] = $oauthSession ['oauth_token_secret'];
		
				include  plugin_dir_path( __FILE__ ).'xero_library/tests/tests.php';
				
				$wpdb->delete( "$wpdb->prefix"."xero_auth", array( 'credential' => 'oauth_token' ) );
					// Delete and add new auth token and secret token to DB
					$wpdb->insert( 
						"$wpdb->prefix"."xero_auth", 
						array( 
							'credential' => 'oauth_token', 
							'value' => $_SESSION['access_token'] 
						), 
						array( 
							'%s', 
							'%s' 
						) 
					);
					$wpdb->delete( "$wpdb->prefix"."xero_auth", array( 'credential' => 'oauth_token_secret' ) );
					$wpdb->insert( 
						"$wpdb->prefix"."xero_auth", 
						array( 
							'credential' => 'oauth_token_secret', 
							'value' => $_SESSION['oauth_token_secret'] 
						), 
						array( 
							'%s', 
							'%s' 
						) 
					);
						
				testLinks ();
			}
		}
		
	}	
		
	
	
?>
	
	<?php if(isset($suceess_message) && $suceess_message != '') echo $suceess_message; ?>
	<div class="xero_input_form">		
		<?php
		if(isset($_SESSION ['oauth'])) echo $_SESSION ['oauth'];
		?>
		<form action="" method="post" enctype="multipart/form-data">
			<div class="xero_credentials">
				<div class="input_fields" style="clear:left;">
					<div class="input_label">Consumer Key</div>
					<input type="text" name="application_key" value="<?php echo $application_key;?>" style="width:350px;" required  >
					<a style=" color: red; font-size: 20px;  font-weight: bold; margin-left: 10px;  padding-top: 7px; width: 20px;" title="Click here to find steps to create xero application" href="http://developer.xero.com/documentation/getting-started/private-applications/#title2" target="_blank">?</a>
				</div>
				<div class="input_fields" style="clear:left;">
					<div class="input_label">Consumer Secret</div>
					<input type="text" name="secret_key" value="<?php echo $secret_key; ?>" style="width:350px;" required>
				</div>
				<div class="input_fields" style="clear:left;">
					<div class="input_label">Private key</div>
					<input type="file" name="fileToUpload" id="fileToUpload" style="width: 351px;float: left;background-color: rgb(255, 255, 255);border: 1px solid rgb(221, 221, 221);" required>
					<a style=" color: red; float: left; font-size: 20px;  font-weight: bold; margin-left: 10px;  padding-top: 7px; width: 20px;" title="Click here to find steps to create private key" href="http://developer.xero.com/documentation/advanced-docs/public-private-keypair/" target="_blank">?</a>
				</div>
				<div class="input_fields" style="clear:left;margin-top: 46px;">
					
					<input type="submit" name="save_data" value="Authenticate" style="background-color: #87CEEB;border-radius: 5px;height: 45px;font-size: 17px;margin-left: 233px;">
				</div>
			</div>
		</form>
	</div>
<?php
	
}
function isxwpe_add_xero_invoice_to_account( )
{
	// Function to add invoices to xero account
	global $wpdb;
	// This if statement start when user complete checkout
	if ( isset( $_REQUEST['wpsc_action'] ) && ($_REQUEST['wpsc_action'] == 'submit_checkout') ) {
		$samp_array = array();
		$results = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."xero_auth" ); // Collect auth credentials 
		if(!empty($results))
		{
			foreach($results as $result)
			{
				if($result->credential == 'application_key') $application_key = $result->value;
				if($result->credential == 'secret_key') $secret_key = $result->value;
				if($result->credential == 'redirect_url') $redirect_url = $result->value;
				if($result->credential == 'oauth_token') $oauth_token = $result->value;
				if($result->credential == 'oauth_verifier') $oauth_verifier = $result->value;
				if($result->credential == 'oauth_token_secret') $oauth_token_secret = $result->value;
				
			}
		}
		$checkout_session_id = wpsc_get_customer_meta( 'checkout_session_id' ); // Find transaction Id
		$results = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."wpsc_purchase_logs", ARRAY_A ); // Collect transaction details
		foreach($results as $temp_results)
		{
			if($temp_results['sessionid'] == $checkout_session_id) $samp_array = $temp_results;
		}
		if(!empty($samp_array))
		{
			$purchase_id = $samp_array['id'];
			$total_price = $samp_array['totalprice'];
			$user_id = $samp_array['user_ID'];
			$user_email = '';
			$name = '';
			$user_details = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."wpsc_submited_form_data WHERE `log_id` = $purchase_id", ARRAY_A );
			foreach($user_details as $user_data)
			{
				if ( is_email( $user_data['value'] ) ) $user_email = $user_data['value'];
				if(isset($user_data['form_id']) && $user_data['form_id'] == 2) $name = $name.$user_data['value'];
				if(isset($user_data['form_id']) && $user_data['form_id'] == 3) $name = $name.' '.$user_data['value'];
				
			}
		}
		$currency = $wpdb->get_results("SELECT * FROM $wpdb->prefix"."options WHERE `option_name` ='currency_type'");
		$currency_id = $currency[0]->option_value;
		$currency_details = $wpdb->get_results("SELECT * FROM $wpdb->prefix"."wpsc_currency_list WHERE id = $currency_id");
		$currency_code = $currency_details[0]->code; // Set currency code for xero same as currency on WPecommerse plugin
		$wpdb->get_results( "SELECT * FROM $wpdb->prefix"."wpsc_cart_contents WHERE purchaseid = $purchase_id " ); // Select product details
		$purchased_items = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."wpsc_cart_contents WHERE purchaseid = $purchase_id " );
			$wp_wpsc_purchase_logs = $wpdb->get_results( "SELECT * FROM $wpdb->prefix"."wpsc_purchase_logs WHERE id = $purchase_id " );
			$wp_wpsc_purchase_log = $wp_wpsc_purchase_logs[0];
			$line_items = '';
			foreach($purchased_items as $selected_items) // Add each item purchased to invoice
			{
				//print_r($purchased_items);
			$line_items .= '<LineItem>
				<Description>'.$selected_items->name.'</Description>
				<Quantity>'.$selected_items->quantity.'</Quantity>
				<TaxAmount>'.$selected_items->tax_charged.'</TaxAmount>
				<UnitAmount>'.$selected_items->price.'</UnitAmount>
			  </LineItem>';
				
			}
		require plugin_dir_path( __FILE__ ).'xero_library/lib/XeroOAuth.php';
		define ( "XRO_APP_TYPE", "Public" );
		$useragent = "Xero-OAuth-PHP Public";
		define ( "OAUTH_CALLBACK", $redirect_url );
		$signatures = array (
		'consumer_key' => $application_key,
		'shared_secret' => $secret_key,
		
		//'oauth_secret' => $shared_secret,
		// API versions
		'core_version' => '2.0',
		'payroll_version' => '1.0',
		'file_version' => '1.0' ,
		'access_token' => $oauth_token,
		//'oauth_verifier' => $oauth_verifier,
		'access_token_secret' => $oauth_token_secret,
		);
		$target_directorys = wp_upload_dir();
		$target_directory = $target_directorys['basedir'];	
		if (XRO_APP_TYPE == "Private" || XRO_APP_TYPE == "Public") {
			$signatures ['rsa_private_key'] = $target_directory.'/xero_invoice/private_keys/privatekey.pem';
			$signatures ['rsa_public_key'] = $target_directory.'/xero_invoice/private_keys/publickey.cer';
		}

		$XeroOAuth = new XeroOAuth ( array_merge ( array (
		'application_type' => XRO_APP_TYPE,
		'oauth_callback' => OAUTH_CALLBACK,
		'user_agent' => $useragent 
		), $signatures ) );
		$initialCheck = $XeroOAuth->diagnostics ();
		$today = date('Y-m-d');
		include plugin_dir_path( __FILE__ ).'tests/testRunner.php';
		$xml = "<Invoices>
                      <Invoice>
                        <Type>ACCREC</Type>
						<CurrencyCode>$currency_code</CurrencyCode>
                        <Contact>
                          <Name>$name</Name>
						  <EmailAddress>$user_email</EmailAddress>
                        </Contact>
                        <Date>$today</Date>
                        <LineAmountTypes>Exclusive</LineAmountTypes>
                        <LineItems>
                          $line_items
                       </LineItems>
                     </Invoice>
                   </Invoices>";
			//$myfile = fopen(WP_PLUGIN_DIR."/invoice-sync-for-xero-and-wpecommerce/XerSyncLog.txt", "w") or die("Unable to open file!");
            $response = $XeroOAuth->request('POST', $XeroOAuth->url('Invoices', 'core'), array(), $xml);			
			
            if ($XeroOAuth->response['code'] == 200) {
				// Add transaction details to DB id transaction is success
                $invoice = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
				$invoice_id = $invoice->Invoices[0]->Invoice->InvoiceNumber;
				$wpdb->insert( 
					"$wpdb->prefix"."xero_history", 
					array( 
						'session_id' => $checkout_session_id,
						'purchase_id' => $purchase_id,
						'invoice_id' => $invoice_id,
						'user_email' => $user_email,
						), 
					array( 
						'%d', 
						'%d',
						'%s',
						'%s'
					) 
				);
				
            } else {
                //outputError($XeroOAuth);				
            }
			
		
	}
}

add_action( 'wpsc_submit_checkout', 'isxwpe_add_xero_invoice_to_account' );

function isxwpe_pagination($totalposts,$p,$lpm1,$prev,$next,$current_url){
    $adjacents = 3;
    if($totalposts > 1)
    {
        $pagination .= '<div class="pagination_main">';
        //previous button
        if ($page> 1)
        $pagination.= "<a href=\"$current_url&pg=$prev\"><< Previous</a> ";
        else
        $pagination.= "<span class=\"disabled\"><< Previous</span> ";
        if ($totalposts < 7 + ($adjacents * 2)){
            for ($counter = 1; $counter <= $totalposts; $counter++){
                if ($counter == $p)
                $pagination.= "<span class=\"current\">$counter</span>";
                else
                $pagination.= " <a href=\"$current_url&pg=$counter\">$counter</a> ";}
        }elseif($totalposts > 5 + ($adjacents * 2)){
            if($page< 1 + ($adjacents * 2)){
                for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++){
                    if ($counter == $p)
                    $pagination.= " <span class=\"current\">$counter</span> ";
                    else
                    $pagination.= " <a href=\"$current_url&pg=$counter\">$counter</a> ";
                }
                $pagination.= " ... ";
                $pagination.= " <a href=\"$current_url&pg=$lpm1\">$lpm1</a> ";
                $pagination.= " <a href=\"$current_url&pg=$totalposts\">$totalposts</a> ";
            }
            //in middle; hide some front and some back
            elseif($totalposts - ($adjacents * 2) > $page&& $page> ($adjacents * 2)){
                $pagination.= " <a href=\"$current_url&pg=1\">1</a> ";
                $pagination.= " <a href=\"$current_url&pg=2\">2</a> ";
                $pagination.= " ... ";
                for ($counter = $page- $adjacents; $counter <= $page+ $adjacents; $counter++){
                    if ($counter == $p)
                    $pagination.= " <span class=\"current\">$counter</span> ";
                    else
                    $pagination.= " <a href=\"$current_url&pg=$counter\">$counter</a> ";
                }
                $pagination.= " ... ";
                $pagination.= " <a href=\"$current_url&pg=$lpm1\">$lpm1</a> ";
                $pagination.= " <a href=\"$current_url&pg=$totalposts\">$totalposts</a> ";
            }else{
                $pagination.= " <a href=\"$current_url&pg=1\">1</a> ";
                $pagination.= " <a href=\"$current_url&pg=2\">2</a> ";
                $pagination.= " ... ";
                for ($counter = $totalposts - (2 + ($adjacents * 2)); $counter <= $totalposts; $counter++){
                    if ($counter == $p)
                    $pagination.= " <span class=\"current\">$counter</span> ";
                    else
                    $pagination.= " <a href=\"$current_url&pg=$counter\">$counter</a> ";
                }
            }
        }
        if ($page< $counter - 1)
        $pagination.= " <a href=\"$current_url&pg=$next\">Next >></a>";
        else
        $pagination.= " <span class=\"disabled\">Next >></span>";
        $pagination.= "</div>";
    }
    return $pagination;
}
function isxwpe_plugin_help()
{
	echo '<h3>About plugin</h3>';
	?>
	<div class="help_content" style="font-size: 15px;">
		<p style="font-size: 15px;"> 
		Xero Sync is an extension of WP eCommerce plugin. This plugin syncs Invoices for all purchases in the WP eCommerce plugin to Xero. For syncing with Xero, this plugin needs a <a href="http://developer.xero.com/documentation/getting-started/private-applications">Xero private application</a> to be registered in the user's Xero Account.</p>
			<div class="steps_tittle" style="font-weight: bold;">Steps to create Xero private application</div>
			<ol>
				<li>Visit <a href="http://developer.xero.com/">http://developer.xero.com/</a> on your browser</li>
				<li>Click 'My Applications' from top menu and login</li>
				<li>Login to your Xero account</li>
				<li>All the application registered in your Xero account will be listed.</li>
				<li>Click 'Add Application' to open application setup page <img class="main_page_img" src="<?php echo plugins_url( '/images/set_up_plugin.png', __FILE__ ); ?>" style="margin: 15px 0px; border: 1px solid #000;width: 86%;">
					<ul style="list-style: circle; padding-left:20px;">
						<li>Select private application</li>
						<li>Add an application name </li> 
						<li>Select Organisation to link xero </li>
						<li>Upload your <a href="http://developer.xero.com/documentation/advanced-docs/public-private-keypair/">key</a></li>
						<li>Click 'Enable Payroll API' and 'Terms and condition' then click save button</li>
					</ul>
				</li>
				<li>Private application will be created will be redirected to the application detail page <img class="application_detalis_img" src="<?php echo plugins_url( '/images/application_detalis.png', __FILE__ ); ?>" style="margin: 15px 0px; border: 1px solid #000;width: 86%;" ></li>
				<li> Collect you consumer key and consumer secret, click show to view/copy your key</li> 
				<li> Add consumer key, consumer secret and private key on Xero Sync settings page</li> 
			</ol>
			
		</p>
	</div>
	<?php
}