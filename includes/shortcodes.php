<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ========== FILTERS SHORTCODE ==========
add_shortcode( 'workable_job_filters', function( $atts ) {
	$jobs = wjb_get_jobs();
	$locations = [];
	$has_remote = false;

	if (!is_wp_error($jobs)) {
		foreach ($jobs as $job) {
			if (isset($job['state']) && !in_array($job['state'], ['open', 'published'])) continue;
			if (!empty($job['sample'])) continue;

			$loc = $job['location'] ?? [];
			$country = isset($loc['country']) ? $loc['country'] : '';
			$city = isset($loc['city']) ? $loc['city'] : '';
			if (!empty($loc['telecommuting']) && $loc['telecommuting']) {
				$has_remote = true;
				continue;
			}
			if (!$country && !$city) continue;

			if (!isset($locations[$country])) $locations[$country] = [];
			if ($city && !in_array($city, $locations[$country])) $locations[$country][] = $city;
		}
	}

	ob_start(); ?>
<div id="wjb-job-filters" class="wjb-filters-container">
	<input type="text" id="wjb-job-search" placeholder="<?php echo esc_attr__('Search job titles...', 'workable-job-board'); ?>">
	<select id="wjb-filter-department"><option value=""><?php echo esc_html__('All Departments', 'workable-job-board'); ?></option></select>
	<select id="wjb-filter-location">
		<option value=""><?php echo esc_html__('Select Office', 'workable-job-board'); ?></option>
		<?php foreach ($locations as $country => $cities): ?>
		<?php if (!$country) continue; ?>
		<option value="<?php echo esc_attr($country); ?>"><?php echo esc_html($country); ?></option>
		<?php foreach ($cities as $city): ?>
		<option value="<?php echo esc_attr($country . '|' . $city); ?>">&ndash; <?php echo esc_html($city); ?></option>
		<?php endforeach; ?>
		<?php endforeach; ?>
		<?php if ($has_remote): ?>
		<option value="Remote"><?php echo esc_html__('Remote', 'workable-job-board'); ?></option>
		<?php endif; ?>
	</select>
</div>
<?php
	return ob_get_clean();
});

// ========== JOB BOARD SHORTCODE ==========
add_shortcode( 'workable_job_board', function( $atts ) {
	$jobs = wjb_get_jobs();

	if ( is_wp_error( $jobs ) ) {
		return '<p>' . esc_html__('Unable to fetch jobs right now. Please try again later.', 'workable-job-board') . '</p>';
	}
	if ( empty( $jobs ) ) {
		return '<p>' . esc_html__('No open positions at this time. Check back soon!', 'workable-job-board') . '</p>';
	}

	$grouped_jobs = [];
	foreach ( $jobs as $job ) {
		if ( isset($job['state']) && !in_array($job['state'], ['open', 'published']) ) continue;
		if ( !empty($job['sample']) ) continue;
		$dept = '';
		if ( !empty($job['department']) ) $dept = $job['department'];
		elseif ( !empty($job['department_hierarchy']) && is_array($job['department_hierarchy']) ) {
			$last = end($job['department_hierarchy']);
			$dept = is_array($last) ? $last['name'] : $last;
		}
		if ( !$dept ) $dept = 'Other';
		$grouped_jobs[$dept][] = $job;
	}

	ob_start();
?>
<div id="wjb-job-board">
	<?php foreach ($grouped_jobs as $dept => $dept_jobs): ?>
	<h2 class="wjb-dept-title"><?php echo esc_html($dept); ?></h2>
	<ul class="wjb-job-list">
		<?php foreach ($dept_jobs as $job):
	$shortcode = isset($job['shortcode']) ? esc_attr($job['shortcode']) : '';
	$title = isset($job['title']) ? esc_html($job['title']) : '';
	$location = !empty($job['location']['location_str']) ? esc_html($job['location']['location_str']) : '';
		?>
		<li class="wjb-job-item" data-shortcode="<?php echo esc_attr($shortcode); ?>">
			<a href="#" class="wjb-view-detail-btn"><strong><?php echo esc_html($title); ?></strong></a>
			<?php if ($location): ?>
			<div class="wjb-job-location"><?php echo esc_html($location); ?></div>
			<?php endif; ?>
		</li>

		<?php endforeach; ?>
	</ul>
	<?php endforeach; ?>
	<div id="wjb-job-detail" style="display:none;"></div>
</div>
<?php
	return ob_get_clean();
});

// ========== AJAX HANDLER ==========
add_action('wp_ajax_nopriv_wjb_fetch_job_detail', 'wjb_fetch_job_detail');
add_action('wp_ajax_wjb_fetch_job_detail',      'wjb_fetch_job_detail');
function wjb_fetch_job_detail() {
	$shortcode = isset($_GET['shortcode']) ? sanitize_text_field($_GET['shortcode']) : '';
	if (!$shortcode) {
		wp_send_json(['error' => true]);
	}
	$job = wjb_get_job($shortcode);
	if (is_wp_error($job)) {
		wp_send_json(['error' => true]);
	}
	$desc = isset($job['full_description']) && $job['full_description']
		? $job['full_description']
		: (isset($job['description']) ? $job['description'] : '');
	$apply_url = isset($job['application_url']) ? $job['application_url'] : 'https://' . esc_attr(get_option('wjb_workable_subdomain', '')) . '.workable.com/j/' . esc_attr($shortcode);
	wp_send_json([
		'title' => isset($job['title']) ? esc_html($job['title']) : '',
		'description' => $desc,
		'apply_url' => $apply_url,
	]);
}
?>
