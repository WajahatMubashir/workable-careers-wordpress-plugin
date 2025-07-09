<?php
// API integration for Workable Job Board plugin

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get a list of published jobs from Workable
 */
function wjb_get_jobs() {
	$subdomain = get_option('wjb_workable_subdomain', '');
	$token = get_option('wjb_api_token', '');
	$url = "https://www.workable.com/spi/v3/accounts/$subdomain/jobs";
	$args = [
		'headers' => [
			'Authorization' => 'Bearer ' . $token,
			'Accept'        => 'application/json'
		],
		'timeout' => 15
	];
	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) ) return $response;
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code !== 200 ) return new WP_Error( 'wjb_api_error', 'Workable API error: ' . $code );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	return isset( $data['jobs'] ) ? $data['jobs'] : [];
}

/**
 * Get a single job's details by shortcode from Workable
 */
function wjb_get_job( $shortcode ) {
	$subdomain = get_option('wjb_workable_subdomain', '');
	$token = get_option('wjb_api_token', '');
	$url = "https://www.workable.com/spi/v3/accounts/$subdomain/jobs/$shortcode";
	$args = [
		'headers' => [
			'Authorization' => 'Bearer ' . $token,
			'Accept'        => 'application/json'
		],
		'timeout' => 15
	];
	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) ) return $response;
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code !== 200 ) return new WP_Error( 'wjb_api_error', 'Workable API error: ' . $code );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	return $data;
}

/**
 * Get the application form structure for a job from Workable
 */
function wjb_get_application_form( $shortcode ) {
	$subdomain = get_option('wjb_workable_subdomain', '');
	$token = get_option('wjb_api_token', '');
	$url = "https://www.workable.com/spi/v3/accounts/$subdomain/jobs/$shortcode/application_form";
	$args = [
		'headers' => [
			'Authorization' => 'Bearer ' . $token,
			'Accept'        => 'application/json'
		],
		'timeout' => 15
	];
	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) ) return $response;
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code !== 200 ) return new WP_Error( 'wjb_api_error', 'Workable API error: ' . $code );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	// Debug: Log the form structure
// 	file_put_contents(__DIR__ . '/form_structure_debug.txt', json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
	return $data;
}

/**
 * Submit a candidate application to Workable (with attachments)
 */

function wjb_submit_application( $shortcode, $candidate, $files ) {
	$subdomain = get_option('wjb_workable_subdomain', '');
	$token = get_option('wjb_api_token', '');
	$url = "https://tcp-software-1.workable.com/spi/v3/jobs/$shortcode/candidates";

	unset($candidate['shortcode'], $candidate['action'], $candidate['nonce']);

	// Prepare candidate array
	$candidate_data = $candidate;

	// If a resume file is uploaded, add base64-encoded data
	if (!empty($files['resume']) && is_uploaded_file($files['resume']['tmp_name'])) {
		$file_contents = file_get_contents($files['resume']['tmp_name']);
		$candidate_data['resume'] = [
			'name' => $files['resume']['name'],
			'data' => base64_encode($file_contents)
		];
	}

	// Set sourced flag if needed (see previous advice)
	$data = [
		'candidate' => $candidate_data,
		'sourced'   => false, // Make sure this is set so they go to Applied stage!
	];

	$json_data = json_encode($data);

// 	file_put_contents(__DIR__ . '/api_json_debug.txt', $json_data . "\n\n", FILE_APPEND);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Authorization: Bearer ' . $token,
		'Accept: application/json',
		'Content-Type: application/json'
	]);

	$response = curl_exec($ch);
	$err = curl_error($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

// 	file_put_contents(__DIR__ . '/api_json_debug.txt', "Status: $status\nResponse: $response\nError: $err\n\n", FILE_APPEND);

	if ($err) {
		return new WP_Error('wjb_api_error', "cURL Error: $err");
	}

	if ($status < 200 || $status >= 300) {
		$message = $response;
		if ($decoded = json_decode($response, true)) {
			if (isset($decoded['error'])) {
				$message = $decoded['error'];
			} elseif (isset($decoded['message'])) {
				$message = $decoded['message'];
			}
		}
		return new WP_Error('wjb_api_error', 'Workable API error: ' . $message);
	}

	return true;
}


// function wjb_submit_application( $shortcode, $candidate, $files ) {
// 	$subdomain = get_option('wjb_workable_subdomain', '');
// 	$token = get_option('wjb_api_token', '');
// 	$url = "https://tcp-software-1.workable.com/spi/v3/jobs/$shortcode/candidates";

// 	unset($candidate['shortcode'], $candidate['action'], $candidate['nonce']);

// 	// Build the multipart payload
// 	$payload = [];
// 	foreach ($candidate as $key => $value) {
// 		$payload["candidate[$key]"] = $value;
// 	}

// 	// Attach the resume file at top-level, NOT nested!
// 	if (!empty($files['resume']) && is_uploaded_file($files['resume']['tmp_name'])) {
// 		$payload['resume'] = new CURLFile(
// 			$files['resume']['tmp_name'],
// 			$files['resume']['type'],
// 			$files['resume']['name']
// 		);
// 	}

// 	// Optional: debug log
// 	file_put_contents(__DIR__ . '/api_json_debug.txt', print_r($payload, true) . "\n\n", FILE_APPEND);

// 	$ch = curl_init();
// 	curl_setopt($ch, CURLOPT_URL, $url);
// 	curl_setopt($ch, CURLOPT_POST, 1);
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
// 	curl_setopt($ch, CURLOPT_HTTPHEADER, [
// 		'Authorization: Bearer ' . $token,
// 		'Accept: application/json'
// 		// Do NOT set Content-Type: multipart/form-data manually!
// 	]);

// 	$response = curl_exec($ch);
// 	$err = curl_error($ch);
// 	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// 	curl_close($ch);

// 	file_put_contents(__DIR__ . '/api_json_debug.txt', "Status: $status\nResponse: $response\nError: $err\n\n", FILE_APPEND);

// 	if ($err) {
// 		return new WP_Error('wjb_api_error', "cURL Error: $err");
// 	}

// 	if ($status < 200 || $status >= 300) {
// 		$message = $response;
// 		if ($decoded = json_decode($response, true)) {
// 			if (isset($decoded['error'])) {
// 				$message = $decoded['error'];
// 			} elseif (isset($decoded['message'])) {
// 				$message = $decoded['message'];
// 			}
// 		}
// 		return new WP_Error('wjb_api_error', 'Workable API error: ' . $message);
// 	}

// 	return true;
// }
