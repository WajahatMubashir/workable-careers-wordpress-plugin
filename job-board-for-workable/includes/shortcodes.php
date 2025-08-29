<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ─────────────────────────  FILTERS  ───────────────────────── */

function wjbfw_render_job_filters() {

	$jobs        = wjbfw_get_jobs();
	$locations   = [];
	$has_remote  = false;

	if ( ! is_wp_error( $jobs ) ) {
		foreach ( $jobs as $job ) {

			/* 1. skip closed / sample posts */
			if ( isset( $job['state'] ) && ! in_array( $job['state'], ['open','published'], true ) ) {
				continue;
			}
			if ( ! empty( $job['sample'] ) ) continue;

			/* 2. unpack location */
			$loc      = $job['location']       ?? [];
			$country  = $loc['country']        ?? '';
			$city     = $loc['city']           ?? '';
			$region   = $loc['region']         ?? '';
			$locStr   = $loc['location_str']   ?? '';
			$isRemote = ! empty( $loc['telecommuting'] );

			/* 3. fallbacks for missing city */
			if ( ! $city && $region )  $city = $region;
			if ( ! $city && $locStr )  $city = trim( strtok( $locStr, ',' ) );

			/* 4. safety defaults */
			if ( ! $country && ! $city ) {
				$country = 'Unknown';
				$city    = 'Unknown';
			}

			/* 5. build country → cities map */
			if ( ! isset( $locations[ $country ] ) ) $locations[ $country ] = [];
			if ( $city && ! in_array( $city, $locations[ $country ], true ) ) {
				$locations[ $country ][] = $city;
			}

			/* 6. remember presence of remote roles */
			if ( $isRemote ) $has_remote = true;
		}
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public job parameter for frontend display
	$has_job  = ! empty( $_GET['job'] );
	$disabled = $has_job ? ' disabled="disabled"' : '';

	ob_start(); ?>
<div id="wjbfw-job-filters" class="wjbfw-filters-container">

	<!-- Search -->
	<label class="screen-reader-text" for="wjbfw-job-search">Search job titles</label>
	<input type="text" id="wjbfw-job-search" placeholder="Search job titles…" <?php echo esc_attr( $disabled ); ?>>

	<!-- Department -->
	<label class="screen-reader-text" for="wjbfw-filter-department">Filter by department</label>
	<select id="wjbfw-filter-department" <?php echo esc_attr( $disabled ); ?> aria-label="Filter by department">
		<option value="">All Departments</option>
	</select>

	<!-- Location filter -->
	<label class="screen-reader-text" for="wjbfw-filter-location">Filter by location</label>
	<select id="wjbfw-filter-location" class="wjbfw-filter">
		<option value="">All locations</option>

		<?php foreach ( $locations as $country => $cities ) : ?>
		<!-- country itself -->
		<option value="<?php echo esc_attr( $country ); ?>">
			<?php echo esc_html( $country ); ?>
		</option>

		<!-- its cities, slightly indented -->
		<?php foreach ( $cities as $c ) : ?>
		<option value="<?php echo esc_attr( $c ); ?>">
			&nbsp;&nbsp;<?php echo esc_html( $c ); ?>
		</option>
		<?php endforeach; ?>
		<?php endforeach; ?>

		<option value="Remote">Remote</option>
	</select>

</div>
<?php
	return ob_get_clean();
}

function wjbfw_render_job_board() {

	$jobs = wjbfw_get_jobs();

	if ( is_wp_error( $jobs ) )  return '<p>Unable to fetch jobs right now. Please try again later.</p>';
	if ( empty( $jobs ) )       return '<p>No open positions at this time. Check back soon!</p>';

	/* group by department */
	$grouped = [];
	foreach ( $jobs as $job ) {
		if ( isset( $job['state'] ) && ! in_array( $job['state'], ['open','published'], true ) ) continue;
		if ( ! empty( $job['sample'] ) ) continue;

		$dept = '';
		if ( ! empty( $job['department'] ) ) {
			$dept = $job['department'];
		} elseif ( ! empty( $job['department_hierarchy'] ) && is_array( $job['department_hierarchy'] ) ) {
			$last = end( $job['department_hierarchy'] );
			$dept = is_array( $last ) ? $last['name'] : $last;
		}
		if ( ! $dept ) $dept = 'Other';

		$grouped[ $dept ][] = $job;
	}

	ob_start(); ?>
<div id="wjbfw-job-board">
	<?php foreach ( $grouped as $dept => $dept_jobs ) : ?>
	<h2 class="wjbfw-dept-title"><?php echo esc_html( $dept ); ?></h2>
	<ul class="wjbfw-job-list">
		<?php foreach ( $dept_jobs as $job ) :

	/* per-job location unpack */
	$loc      = $job['location']     ?? [];
	$country  = $loc['country']      ?? '';
	$city     = $loc['city']         ?? '';
	$region   = $loc['region']       ?? '';
	$locStr   = $loc['location_str'] ?? '';
	$isRemote = ! empty( $loc['telecommuting'] );

	if ( ! $city && $region )  $city = $region;
	if ( ! $city && $locStr )  $city = trim( strtok( $locStr, ',' ) );
	if ( ! $country && ! $city ) { $country = 'Unknown'; $city = 'Unknown'; }

	$shortcode = esc_attr( $job['shortcode'] );
	$title     = esc_html( $job['title'] );
	$locLabel  = $locStr ? esc_html( $locStr ) : '';
		?>
		<li class="wjbfw-job-item"
			data-country="<?php echo esc_attr( $country ); ?>"
			data-city="<?php echo esc_attr( $city ); ?>"
			data-remote="<?php echo $isRemote ? '1' : '0'; ?>"
			data-department="<?php echo esc_attr( $dept ); ?>"
			data-shortcode="<?php echo esc_attr( $shortcode ); ?>">

			<a href="#" class="wjbfw-view-detail-btn"
			   aria-label="View details about <?php echo esc_attr( $title ); ?>">
				<strong class="wjbfw-job-title"><?php echo esc_html( $title ); ?></strong>
			</a>

			<?php if ( $locLabel ) : ?>
			<div class="wjbfw-job-location"><?php echo esc_html( $locLabel ); ?></div>
			<?php endif; ?>

		</li>
		<?php endforeach; ?>
	</ul>
	<?php endforeach; ?>

	<div id="wjbfw-job-detail" style="display:none;"></div>
</div>

<?php
	return ob_get_clean();
}

// Register shortcodes with proper prefixes
add_shortcode( 'wjbfw_job_filters', 'wjbfw_render_job_filters' );
add_shortcode( 'wjbfw_job_board', 'wjbfw_render_job_board' );

// Maintain backward compatibility with old shortcode names (temporary)
add_shortcode( 'job_board_for_workable_filters', 'wjbfw_render_job_filters' );
add_shortcode( 'job_board_for_workable', 'wjbfw_render_job_board' );


/* ─────────────────────────  AJAX  ───────────────────────── */

add_action( 'wp_ajax_nopriv_wjbfw_fetch_job_detail', 'wjbfw_fetch_job_detail' );
add_action( 'wp_ajax_wjbfw_fetch_job_detail',        'wjbfw_fetch_job_detail' );
function wjbfw_fetch_job_detail() {
	check_ajax_referer( 'wjbfw_nonce', 'nonce' );

	$shortcode = isset( $_GET['shortcode'] ) ? sanitize_text_field( wp_unslash( $_GET['shortcode'] ) ) : '';
	if ( ! $shortcode || ! preg_match('/^[A-Za-z0-9\-]+$/', $shortcode) ) {
		wp_send_json_error( 'Invalid shortcode', 400 );
	}

	$job = wjbfw_get_job( $shortcode );
	if ( is_wp_error( $job ) ) {
		wp_send_json_error( 'Not found', 404 );
	}

	$desc = ! empty( $job['full_description'] ) ? $job['full_description']
		: ( $job['description'] ?? '' );

	$subdomain = get_option('wjbfw_workable_subdomain', '');
	$apply = $job['application_url']
		?? ( $subdomain ? 'https://' . $subdomain . '.workable.com/j/' . $shortcode : '' );

	wp_send_json_success( array(
		'title'       => sanitize_text_field( $job['title'] ?? '' ),
		'description' => wp_kses_post( $desc ),
		'apply_url'   => esc_url_raw( $apply ),
	) );
}
