<?php
/*
Plugin Name: Simple Woo to SugarCRM
Description: Send WooCommerce Customers to SugarCRM as leads.
Version: 1.0
Author: Nate Spring
*/


if (!defined('ABSPATH')) exit; // Exit if accessed directly



class SWTSSettingsPage
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'Settings Admin',
			'Woo to Sugar Settings',
			'manage_options',
			'swts-setting-admin',
			array($this, 'create_admin_page')
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option('swts_option_name');
?>
		<div class="wrap">
			<h1>Welcome to Simple Woo to SugarCRM Settings!</h1>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('swts_option_group');
				do_settings_sections('swts-setting-admin');
				submit_button();
				?>
			</form>
		</div>
<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting(
			'swts_option_group', // Option group
			'swts_option_name', // Option name
			array($this, 'sanitize') // Sanitize
		);

		add_settings_section(
			'setting_section_id', // ID
			'Simple Woo to Sugar Settings:', // Title
			array($this, 'print_section_info'), // Callback
			'swts-setting-admin' // Page
		);

		add_settings_field(
			'username', // ID
			'SugarCRM Username', // Title 
			array($this, 'username_callback'), // Callback
			'swts-setting-admin', // Page
			'setting_section_id' // Section           
		);

		add_settings_field(
			'password',
			'SurgarCRM Password',
			array($this, 'password_callback'),
			'swts-setting-admin',
			'setting_section_id'
		);

		add_settings_field(
			'team_id',
			'Team ID',
			array($this, 'team_id_callback'),
			'swts-setting-admin',
			'setting_section_id'
		);

		add_settings_field(
			'business_type',
			'Business Type',
			array($this, 'business_type_callback'),
			'swts-setting-admin',
			'setting_section_id'
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize($input)
	{
		$new_input = array();
		if (isset($input['username']))
			$new_input['username'] = sanitize_text_field($input['username']);

		if (isset($input['password']))
			$new_input['password'] = sanitize_text_field($input['password']);

		if (isset($input['team_id']))
			$new_input['team_id'] = sanitize_text_field($input['team_id']);

		if (isset($input['business_type']))
			$new_input['business_type'] = sanitize_text_field($input['business_type']);

		return $new_input;
	}

	/** 
	 * Print the Section text
	 */
	public function print_section_info()
	{
		print 'Enter your settings below to connect to SugarCRM, the desired Team, and Business Type:';
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function username_callback()
	{
		printf(
			'<input type="text" id="username" name="swts_option_name[username]" value="%s" />',
			isset($this->options['username']) ? esc_attr($this->options['username']) : ''
		);
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function password_callback()
	{
		printf(
			'<input type="text" id="password" name="swts_option_name[password]" value="%s" />',
			isset($this->options['password']) ? esc_attr($this->options['password']) : ''
		);
	}

	public function team_id_callback()
	{
		printf(
			'<input type="text" id="team_id" name="swts_option_name[team_id]" value="%s" />',
			isset($this->options['team_id']) ? esc_attr($this->options['team_id']) : ''
		);
	}

	public function business_type_callback()
	{
		printf(
			'<input type="text" id="business_type" name="swts_option_name[business_type]" value="%s" />',
			isset($this->options['business_type']) ? esc_attr($this->options['business_type']) : ''
		);
	}
}

if (is_admin())
	$swts_settings_page = new SWTSSettingsPage();



//SFNap Function
add_action('woocommerce_thankyou', 'wpwc_to_sugar');
function wpwc_to_sugar($order_id)
{
	// get order object and order details
	$order = new WC_Order($order_id);
	$email = $order->billing_email;
	$phone = $order->billing_phone;
	$fname = $order->get_billing_first_name();
	$lname = $order->get_billing_last_name();
	$billCountry = $order->get_billing_country();
	$billAddress1 = $order->get_billing_address_1();
	$billAddress2 = $order->get_billing_address_2();
	$billCity = $order->get_billing_city();
	$billState = $order->get_billing_state();
	$billZip = $order->get_billing_postcode();
	$shipCountry = $order->get_shipping_country();
	$shipAddress1 = $order->get_shipping_address_1();
	$shipAddress2 = $order->get_shipping_address_2();
	$shipCity = $order->get_shipping_city();
	$shipState = $order->get_shipping_state();
	$shipZip = $order->get_shipping_postcode();


	$settings = get_option('swts_option_name');
	$url = "https://intheditch.sugarondemand.com/service/v4_1/rest.php";
	$username = $settings['username'];
	$password = $settings['password'];
	$teamId = $settings['team_id'];
	$businessType = $settings['business_type'];

	//function to make cURL request
	function call($method, $parameters, $url)
	{

		$jsonEncodedData = json_encode($parameters);

		$post = array(
			"method" => $method,
			"input_type" => "JSON",
			"response_type" => "JSON",
			"rest_data" => $jsonEncodedData
		);

		$args = array(
			'method' => 'POST',
			'body'        => $post,
			'httpversion' => '1.0',
			'blocking'    => true,
			'sslverify' => true,
			'headers'     => array(),
			'cookies'     => array(),
		);

		$body_post = wp_remote_post($url, $args);
		$response = json_decode($body_post['body']);



		//echo "<pre>";
		//print_r($response);
		//echo "</pre>";
		return $response;
	}

	//login ---------------------------------------------     

	$login_parameters = array(
		"user_auth" => array(
			"user_name" => $username,
			"password" => md5($password),
			"version" => "1"
		),
		"application_name" => "RestTest",
		"name_value_list" => array(),
	);

	$login_result = call("login", $login_parameters, $url);


	//echo "<pre>";
	//print_r($response);
	//echo "</pre>";


	//get session id
	$session_id = $login_result->id;

	////start of record id search



	$search_by_module_parameters = array(
		"session" => $session_id,
		'search_string' => $email,
		'modules' => array(
			//'Accounts',
			//'Contacts',
			'Leads',
		),
		'offset' => 0,
		'max_results' => 1,
		'assigned_user_id' => '',
		'select_fields' => array('id'),
		'unified_search_only' => false,
		'favorites' => false
	);

	$search_by_module_results = call('search_by_module', $search_by_module_parameters, $url);


	//echo '<pre>';
	///print_r($search_by_module_results);
	//echo '</pre>';


	$record_ids = array();
	foreach ($search_by_module_results->entry_list as $results) {
		$module = $results->name;

		foreach ($results->records as $records) {
			foreach ($records as $record) {
				if ($record->name = 'id') {
					$record_ids[$module][] = $record->value;
					//skip any additional fields
					break;
				}
			}
		}
	}
	//This section of code isnt very necessary for adding leads but is usefull for endpoint testing.
	$get_entries_results = array();
	$modules = array_keys($record_ids);

	foreach ($modules as $module) {
		$get_entries_parameters = array(
			//session id
			'session' => $session_id,

			//The name of the module from which to retrieve records
			'module_name' => $module,

			//An array of record IDs
			'ids' => $record_ids[$module],

			//The list of fields to be returned in the results
			'select_fields' => array(
				'name',
			),

			//A list of link names and the fields to be returned for each link name
			'link_name_to_fields_array' => array(
				array(
					'name' => 'email_addresses',
					'value' => array(
						'email_address',
						'opt_out',
						'primary_address'
					),
				),
			),

			//Flag the record as a recently viewed item
			'track_view' => false,
		);

		$get_entries_results[$module] = call('get_entries', $get_entries_parameters, $url);
	}

	//echo '<pre>';
	//print_r($get_entries_results);
	//echo '</pre>';


	//create account -------------------------------------     
	$set_entry_parameters = array(
		//session id
		"session" => $session_id,

		//The name of the module from which to retrieve records.
		"module_name" => "Leads",

		//Record attributes
		"name_value_list" => array(
			//to update a record, you will nee to pass in a record id as commented below
			array('name' => 'first_name', 'value' => $fname),
			array('name' => 'last_name', 'value' => $lname),
			array('name' => 'account_name', 'value' => $lname . ', ' . $fname),
			array('name' => 'email', 'value' => array(
				array(
					'email_address' => $email,
					'primary_address' => true
				)
			),),
			array('name' => 'primary_address_street', 'value' => $billAddress1 . ' ' . $billAddress2),
			array('name' => 'primary_address_city', 'value' => $billCity),
			array('name' => 'primary_address_state', 'value' => $billState),
			array('name' => 'primary_address_postalcode', 'value' => $billZip),
			array('name' => 'primary_address_country', 'value' => $billCountry),
			array('name' => 'alt_address_street', 'value' => $shipAddress1 . ' ' . $shipAddress2),
			array('name' => 'alt_address_city', 'value' => $shipCity),
			array('name' => 'alt_address_state', 'value' => $shipState),
			array('name' => 'alt_address_postalcode', 'value' => $shipZip),
			array('name' => 'alt_address_country', 'value' => $shipCountry),

			array('name' => 'phone_work', 'value' => $phone),
			array('name' => 'business_type_c', 'value' => $businessType),
			array('name' => 'team_id', 'value' => $teamId),
			array('name' => 'lead_source', 'value' => 'Web Site'),
			array('name' => 'ffp_account_type_c', 'value' => 'End User') .
				array('name' => 'rbo_account_type_c', 'value' => 'End User')
		),
	);


	$set_entry_parameters_exists = array(
		//session id
		"session" => $session_id,

		//The name of the module from which to retrieve records.
		"module_name" => "Leads",

		//Record attributes
		"name_value_list" => array(
			//to update a record, you will nee to pass in a record id as commented below
			array("name" => "id", "value" => $record_ids[$module][] = $record->value),
			array('name' => 'first_name', 'value' => $fname),
			array('name' => 'last_name', 'value' => $lname),
			array('name' => 'account_name', 'value' => $lname . ', ' . $fname),
			array('name' => 'email', 'value' => array(
				array(
					'email_address' => $email,
					'primary_address' => true
				)
			),),
			array('name' => 'primary_address_street', 'value' => $billAddress1 . ' ' . $billAddress2),
			array('name' => 'primary_address_city', 'value' => $billCity),
			array('name' => 'primary_address_state', 'value' => $billState),
			array('name' => 'primary_address_postalcode', 'value' => $billZip),
			array('name' => 'primary_address_country', 'value' => $billCountry),
			array('name' => 'alt_address_street', 'value' => $shipAddress1 . ' ' . $shipAddress2),
			array('name' => 'alt_address_city', 'value' => $shipCity),
			array('name' => 'alt_address_state', 'value' => $shipState),
			array('name' => 'alt_address_postalcode', 'value' => $shipZip),
			array('name' => 'alt_address_country', 'value' => $shipCountry),

			array('name' => 'phone_work', 'value' => $phone),
			array('name' => 'business_type_c', 'value' => $businessType),
			array('name' => 'team_id', 'value' => $teamId),
			array('name' => 'lead_source', 'value' => 'Web Site'),
			array('name' => 'ffp_account_type_c', 'value' => 'End User'),
			array('name' => 'rbo_account_type_c', 'value' => 'End User')

		),
	);
	//Determine if lead is already recorded, if not create new record.
	if ($record_ids[$module][] = $record->value) {
		//echo "Record ID status: FOUND ->" . $record_ids[$module][] = $record->value;
		$set_entry_result = call("set_entry", $set_entry_parameters_exists, $url);
	} else {
		//echo "Record ID status: NOT FOUND -> Creating new lead";
		$set_entry_result = call("set_entry", $set_entry_parameters, $url);
	}
}
