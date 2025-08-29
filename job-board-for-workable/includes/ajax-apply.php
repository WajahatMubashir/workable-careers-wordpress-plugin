<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get application form structure for a job (AJAX).
 */
add_action( 'wp_ajax_wjbfw_get_application_form', 'wjbfw_ajax_get_application_form' );
add_action( 'wp_ajax_nopriv_wjbfw_get_application_form', 'wjbfw_ajax_get_application_form' );
function wjbfw_ajax_get_application_form() {
	check_ajax_referer( 'wjbfw_nonce', 'nonce' );

	$shortcode = sanitize_text_field( wp_unslash( $_POST['shortcode'] ?? '' ) );
	if ( ! $shortcode || ! preg_match( '/^[A-Za-z0-9\-]+$/', $shortcode ) ) {
		wp_send_json_error( 'Invalid shortcode', 400 );
	}

	require_once __DIR__ . '/api.php';
	$form = wjbfw_get_application_form( $shortcode );

	if ( is_wp_error( $form ) ) {
		wp_send_json_error( $form->get_error_message(), 500 );
	} else {
		wp_send_json_success( $form );
	}
}

/**
 * Submit application (AJAX).
 */
add_action( 'wp_ajax_wjbfw_submit_application', 'wjbfw_ajax_submit_application' );
add_action( 'wp_ajax_nopriv_wjbfw_submit_application', 'wjbfw_ajax_submit_application' );
function wjbfw_ajax_submit_application() {
	check_ajax_referer( 'wjbfw_nonce', 'nonce' );

	$shortcode = sanitize_text_field( wp_unslash( $_POST['shortcode'] ?? '' ) );
	if ( ! $shortcode || ! preg_match( '/^[A-Za-z0-9\-]+$/', $shortcode ) ) {
		wp_send_json_error( 'Invalid shortcode', 400 );
	}

	require_once __DIR__ . '/api.php';
	$form = wjbfw_get_application_form( $shortcode );
	if ( is_wp_error( $form ) || empty( $form['form_fields'] ) ) {
		wp_send_json_error( 'Form unavailable', 400 );
	}

	// ===== Whitelist fields from the form schema =====
	$candidate     = array();
	$allowed_files = array();

	foreach ( $form['form_fields'] as $field ) {
		$key  = $field['key'] ?? '';
		$type = $field['type'] ?? 'text';
		if ( ! $key ) {
			continue;
		}

		if ( 'file' === $type ) {
			$allowed_files[] = $key;
			continue;
		}

		// Sanitize by type
		if ( 'email' === $type ) {
			$candidate[ $key ] = sanitize_email( wp_unslash( $_POST[ $key ] ?? '' ) );
			if ( $candidate[ $key ] && ! is_email( $candidate[ $key ] ) ) {
				wp_send_json_error( 'Invalid email address', 400 );
			}
		} elseif ( in_array( $type, array( 'text', 'short_text', 'string' ), true ) ) {
			$candidate[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
		} elseif ( in_array( $type, array( 'textarea', 'free_text' ), true ) ) {
			$candidate[ $key ] = sanitize_textarea_field( wp_unslash( $_POST[ $key ] ?? '' ) );
		} elseif ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			$val     = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
			$choices = array_map( 'sanitize_text_field', (array) ( $field['options'] ?? array() ) );
			if ( $val && ! in_array( $val, $choices, true ) ) {
				wp_send_json_error( 'Invalid selection', 400 );
			}
			$candidate[ $key ] = $val;
		} elseif ( 'date' === $type ) {
			$candidate[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
		} elseif ( 'boolean' === $type ) {
			$candidate[ $key ] = ! empty( $_POST[ $key ] );
		} else {
			// safe default
			$candidate[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
		}

		// Required fields
		if ( ! empty( $field['required'] ) && empty( $candidate[ $key ] ) ) {
			$label = isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : $key;
			wp_send_json_error( "The field '{$label}' is required.", 400 );
		}
	}

	// Ensure name and email exist
	if ( empty( $candidate['name'] ) ) {
		$candidate['name'] = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	}
	if ( empty( $candidate['email'] ) ) {
		$candidate['email'] = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	}
	if ( empty( $candidate['name'] ) || empty( $candidate['email'] ) ) {
		wp_send_json_error( 'Name and Email are required fields.', 400 );
	}
	if ( ! is_email( $candidate['email'] ) ) {
		wp_send_json_error( 'Invalid email address', 400 );
	}

	// ===== Files: only process allowed file fields (validated & sanitized) =====
	$files         = array();
	$allowed_exts  = array( 'pdf', 'doc', 'docx' );
	$allowed_mimes = array(
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);

	foreach ( $allowed_files as $fk ) {
		// Missing field: required vs optional
		if ( empty( $_FILES ) || ! isset( $_FILES[ $fk ] ) || ! is_array( $_FILES[ $fk ] ) ) {
			foreach ( $form['form_fields'] as $field ) {
				if ( ( $field['key'] ?? '' ) === $fk && ! empty( $field['required'] ) ) {
					$label = isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : $fk;
					wp_send_json_error( "The field '{$label}' is required.", 400 );
				}
			}
			continue;
		}

		// Extract each key with guards; sanitize/validate immediately (no raw pass-through)
		$file_error = isset( $_FILES[ $fk ]['error'] ) ? (int) $_FILES[ $fk ]['error'] : UPLOAD_ERR_NO_FILE;
		$file_size  = isset( $_FILES[ $fk ]['size'] ) ? (int) $_FILES[ $fk ]['size'] : 0;

		// tmp_name: server path; cannot be 'sanitized'—validate via is_uploaded_file().
		$file_tmp = '';
		if ( isset( $_FILES[ $fk ]['tmp_name'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is a server-provided path; validated via is_uploaded_file() below.
			$file_tmp = $_FILES[ $fk ]['tmp_name'];
		}

		// name: sanitize immediately
		$file_name = '';
		if ( isset( $_FILES[ $fk ]['name'] ) ) {
			$file_name = sanitize_file_name( wp_unslash( (string) $_FILES[ $fk ]['name'] ) );
		}

		// type: sanitize mime string (also re-derived via wp_check_filetype_and_ext below)
		$file_type_in = isset( $_FILES[ $fk ]['type'] ) ? sanitize_mime_type( $_FILES[ $fk ]['type'] ) : '';

		// Required presence & upload status
		if ( UPLOAD_ERR_OK !== $file_error ) {
			foreach ( $form['form_fields'] as $field ) {
				if ( ( $field['key'] ?? '' ) === $fk && ! empty( $field['required'] ) ) {
					$label = isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : $fk;
					wp_send_json_error( "The field '{$label}' is required.", 400 );
				}
			}
			continue;
		}

		if ( ! $file_tmp || ! $file_name || ! is_uploaded_file( $file_tmp ) ) {
			wp_send_json_error( 'Upload failed.', 400 );
		}

		// Extension whitelist
		$ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_exts, true ) ) {
			wp_send_json_error( 'Unsupported file type. Allowed: ' . implode( ', ', $allowed_exts ), 400 );
		}

		// Extension/mime combo check
		$check = wp_check_filetype_and_ext( $file_tmp, $file_name, $allowed_mimes );
		if ( empty( $check['ext'] ) || $check['ext'] !== $ext ) {
			wp_send_json_error( 'File type mismatch.', 400 );
		}

		// Size check (schema-specific)
		foreach ( $form['form_fields'] as $field ) {
			if ( ( $field['key'] ?? '' ) === $fk && isset( $field['max_file_size'] ) ) {
				if ( $file_size > (int) $field['max_file_size'] ) {
					$label = isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : $fk;
					wp_send_json_error(
						"File '{$label}' is too large. Max: " . round( (int) $field['max_file_size'] / 1024 / 1024, 2 ) . 'MB',
						400
					);
				}
			}
		}

		// Pass a sanitized subset forward (no raw superglobal)
		$files[ $fk ] = array(
			'tmp_name' => $file_tmp, // validated with is_uploaded_file()
			'name'     => $file_name,
			'type'     => sanitize_mime_type( $check['type'] ?? $file_type_in ),
			'size'     => $file_size,
			'error'    => $file_error,
		);
	}

	// ===== Submit to Workable =====
	$result = wjbfw_submit_application( $shortcode, $candidate, $files );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message(), 500 );
	} else {
		wp_send_json_success( 'Application submitted successfully!' );
	}
}
