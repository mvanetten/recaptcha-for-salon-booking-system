<?php

/*
  Plugin Name: reCAPTCHA for Salon Booking System 
  Description: reCAPTCHA for Salon Booking System
  Version: 1.0.0
  Plugin URI: https://github.com/mvanetten/recaptcha-for-salon-booking-system
  Author: M van Etten 
  	
	
  reCAPTCHA for Salon Booking System to protect from spam abuse.
  Copyright (C) 2019  M. van Etten

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, version 3 of the License, or
  any later version..

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <https://www.gnu.org/licenses/>.
  
 */
 

/* prevent direct access to your files */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* include settingspage if user is admin */
if (is_admin()){
	include_once('rfsbs_recaptcha_addon_settings.php');		
}

/* a pre-check before using this plugin */
register_activation_hook( __FILE__, 'rfsbs_recaptcha_install' );

/* invoke hook of third party plugin */
add_action('sln.template.summary.after_total_amount', 'rfsbs_recaptcha_render_recaptcha'); //render recaptcha
add_action('sln.shortcode.summary.dispatchForm.before_booking_creation', 'rfsbs_recaptcha_validate'); //validate recaptcha


/*
 function : rfsbs_recaptcha_install
 description : Checks for plugin dependency and minimal version 
 parameters : 
 return : void
*/  
function rfsbs_recaptcha_install(){
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	$dependencyplugin = ABSPATH . 'wp-content/plugins/salon-booking-system/salon.php';
	$plugindata = get_plugin_data($dependencyplugin);
	$majorversion = explode(".",$plugindata['Version']);
	
	$rfsbs_errors = array();
	
	/* plugin must be active */
	if (!is_plugin_active('salon-booking-system/salon.php')){
		$rfsbs_errors[] = 'Plugin <a href="https://wordpress.org/plugins/salon-booking-system/">Salon Booking System</a> is not active.';
		error_log('Plugin Salon Booking System is not active.',0);
	}

	/* plugin must be version 3 or heiger */
	if ($majorversion[0] < 3){
		$rfsbs_errors[] = 'Plugin version 3 of <a href="https://wordpress.org/plugins/salon-booking-system/">Salon Booking System</a> is required.';
		error_log('Plugin version 3 of Salon Booking System is required.',0);
	}
	
	/* deactivate plugin if errors are found and create a error */
	if(count($rfsbs_errors) > 0 ){
		deactivate_plugins( basename( __FILE__ ) );
		wp_die('<strong>reCAPTCHA Plugin for Booking Salon Errors: </h1><p>' . implode('\n',$rfsbs_errors).'</p>','' ,  array( 'response'=>200, 'back_link'=>true ) );
	}
	

}


/*
 function : rfsbs_recaptcha_validate
 description : Checks if recaptcha is valid. If not it adds a error to the BookingObject
 parameters : BookingObject from third party plugin
 return : void
*/  
function rfsbs_recaptcha_validate($a)
{
	if (!rfsbs_recaptcha_is_valid()){
		$a->addError('Invalid Recaptcha');	
	}
}


/*
 function : rfsbs_recaptcha_render_recaptcha
 description : enqueue scripts and renders recaptcha div
 parameters : 
 return : void
*/  
function rfsbs_recaptcha_render_recaptcha(){
	wp_enqueue_script( 'rfsbs_recaptcha_api', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback',true);
	wp_enqueue_script( 'rfsbs_recaptcha_javascript', plugin_dir_url( __FILE__ ) .'scripts/recaptcha.js',false);
	?>
		<div class='col-xs-12 recaptcha'>
			<div class='row rfsbs-summary-row'>
				<div class='g-recaptcha' data-sitekey='<?php echo get_option('rfsbs_recaptcha_sitekey') ?>' data-size='invisible' data-callback='setResponse'></div>
			</div>
		</div>
	<?php
	
}


/*
 function : rfsbs_recaptcha_is_valid
 description : checks if recaptcha response is valid at google server
 parameters : 
 return : bool (true/false)
*/  
function rfsbs_recaptcha_is_valid(){
    $siteverifyurl = 'https://www.google.com/recaptcha/api/siteverify';
    $response = filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING);
    $data = array('secret' => get_option('rfsbs_recaptcha_privatekey'), 'response' => $response);
    $options = array(
        'http' => array(
          'header'  => 'Content-type: application/x-www-form-urlencoded\r\n',
          'method'  => 'POST',
          'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
	$verify = file_get_contents($siteverifyurl, false, $context);
    $captcha_success=json_decode($verify);
    return $captcha_success->success;
}