<?php
// API integration for Job Board for Workable plugin

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get a list of published jobs from Workable (with pagination)
 */
function wjbfw_get_jobs() {
	$subdomain = get_option('wjbfw_workable_subdomain', '');
	$token = get_option('wjbfw_api_token', '');
	
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public cache refresh parameter
	$bypass_cache = isset($_GET['wjbfw_refresh']) && $_GET['wjbfw_refresh'] === '1';
	
	// Check cache first (unless bypassed)
	if (!$bypass_cache) {
		$cached_jobs = get_transient('wjbfw_jobs_cache');
		if ($cached_jobs !== false) {
			return $cached_jobs;
		}
	}
	
	$all_jobs = [];
	$url = "https://www.workable.com/spi/v3/accounts/$subdomain/jobs?limit=50";
	
	$args = [
		'headers' => [
			'Authorization' => 'Bearer ' . $token,
			'Accept'        => 'application/json'
		],
		'timeout' => 30 // Increased timeout for multiple requests
	];
	
	// Fetch all pages
	while ($url) {
		$response = wp_remote_get($url, $args);
		
		if (is_wp_error($response)) {
			return $response;
		}
		
		$code = wp_remote_retrieve_response_code($response);
		if ($code !== 200) {
			return new WP_Error('wjbfw_api_error', 'Workable API error: ' . $code);
		}
		
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);
		
		if (!isset($data['jobs'])) {
			break;
		}
		
		// Merge jobs from this page
		$all_jobs = array_merge($all_jobs, $data['jobs']);
		
		// Check for next page
		$url = null;
		if (isset($data['paging']['next']) && !empty($data['paging']['next'])) {
			$url = $data['paging']['next'];
		}
	}
	
	// Cache the results for 5 minutes
	set_transient('wjbfw_jobs_cache', $all_jobs, 5 * MINUTE_IN_SECONDS);
	
	return $all_jobs;
}

/**
 * Get a single job's details by shortcode from Workable
 */
function wjbfw_get_job( $shortcode ) {
	$subdomain = get_option('wjbfw_workable_subdomain', '');
	$token = get_option('wjbfw_api_token', '');
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
	if ( $code !== 200 ) return new WP_Error( 'wjbfw_api_error', 'Workable API error: ' . $code );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	return $data;
}

/**
 * Get the application form structure for a job from Workable
 */
function wjbfw_get_application_form( $shortcode ) {
	$subdomain = get_option('wjbfw_workable_subdomain', '');
	$token = get_option('wjbfw_api_token', '');
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
	if ( $code !== 200 ) return new WP_Error( 'wjbfw_api_error', 'Workable API error: ' . $code );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	// Debug: Log the form structure
// 	file_put_contents(__DIR__ . '/form_structure_debug.txt', json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
	return $data;
}

/**
 * Submit a candidate application to Workable (with attachments)
 */

function wjbfw_submit_application( $shortcode, $candidate, $files ) {
	$subdomain = get_option('wjbfw_workable_subdomain', '');
	$token = get_option('wjbfw_api_token', '');
	$url = "https://www.workable.com/spi/v3/accounts/$subdomain/jobs/$shortcode/candidates";

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

	// Use WordPress HTTP API instead of cURL
	$args = [
		'method' => 'POST',
		'headers' => [
			'Authorization' => 'Bearer ' . $token,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		],
		'body' => $json_data,
		'timeout' => 30
	];

	$response = wp_remote_post($url, $args);

	if (is_wp_error($response)) {
		return new WP_Error('wjbfw_api_error', 'HTTP Error: ' . $response->get_error_message());
	}

	$status = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);

	if ($status < 200 || $status >= 300) {
		$message = $body;
		if ($decoded = json_decode($body, true)) {
			if (isset($decoded['error'])) {
				$message = $decoded['error'];
			} elseif (isset($decoded['message'])) {
				$message = $decoded['message'];
			}
		}
		return new WP_Error('wjbfw_api_error', 'Workable API error: ' . $message);
	}

	return true;
}


// function wjbfw_submit_application( $shortcode, $candidate, $files ) {
// 	$subdomain = get_option('wjbfw_workable_subdomain', '');
// 	$token = get_option('wjbfw_api_token', '');
// 	$url = "https://www.workable.com/spi/v3/accounts/$subdomain/jobs/$shortcode/candidates";

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
// 		return new WP_Error('wjbfw_api_error', "cURL Error: $err");
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
// 		return new WP_Error('wjbfw_api_error', 'Workable API error: ' . $message);
// 	}

// 	return true;
// }
