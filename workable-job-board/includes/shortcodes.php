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
            $country = $loc['country'] ?? '';
            $city = $loc['city'] ?? '';
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
    <input type="text" id="wjb-job-search" placeholder="Search job titles...">
    <select id="wjb-filter-department"><option value="">All Departments</option></select>
    <select id="wjb-filter-location">
        <option value="">Select Office</option>
        <?php foreach ($locations as $country => $cities): ?>
        <?php if (!$country) continue; ?>
        <option value="<?php echo esc_attr($country); ?>"><?php echo esc_html($country); ?></option>
        <?php foreach ($cities as $city): ?>
        <option value="<?php echo esc_attr($country . '|' . $city); ?>">&ndash; <?php echo esc_html($city); ?></option>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ($has_remote): ?>
        <option value="Remote">Remote</option>
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
        return '<p>Unable to fetch jobs right now. Please try again later.</p>';
    }
    if ( empty( $jobs ) ) {
        return '<p>No open positions at this time. Check back soon!</p>';
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
    $shortcode = esc_attr($job['shortcode']);
    $title = esc_html($job['title']);
    $location = !empty($job['location']['location_str']) ? esc_html($job['location']['location_str']) : '';
        ?>
        <li class="wjb-job-item" data-shortcode="<?php echo $shortcode; ?>">
            <a href="#" class="wjb-view-detail-btn"><strong><?php echo $title; ?></strong></a>
            <?php if ($location): ?>
            <div class="wjb-job-location"><?php echo $location; ?></div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endforeach; ?>
    <div id="wjb-job-detail" style="display:none;"></div>
</div>
<script>
var wjb_ajax = <?php echo json_encode([
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'nonce'    => wp_create_nonce( 'wjb_nonce' ),
]); ?>;
</script>
<script>
// This code is also a reference for workable-job-board.js
document.addEventListener('DOMContentLoaded', function() {
    // --- Populate department dropdown ---
    var deptSelect = document.getElementById('wjb-filter-department');
    var locSelect = document.getElementById('wjb-filter-location');
    var searchInput = document.getElementById('wjb-job-search');
    if (deptSelect) {
        var depts = new Set();
        document.querySelectorAll('.wjb-dept-title').forEach(function(h2){
            var dept = h2.textContent.trim();
            if (dept) depts.add(dept);
        });
        depts.forEach(function(dept){
            var opt = document.createElement('option');
            opt.value = dept;
            opt.textContent = dept;
            deptSelect.appendChild(opt);
        });
    }

    // --- Filtering logic ---
    function filterJobs(){
        var dept = deptSelect ? deptSelect.value.toLowerCase() : '';
        var locValue = locSelect ? locSelect.value : '';
        var query = searchInput ? searchInput.value.toLowerCase() : '';
        document.querySelectorAll('.wjb-dept-title').forEach(function(h2){
            var ul = h2.nextElementSibling;
            var anyVisible = false;
            ul.querySelectorAll('.wjb-job-item').forEach(function(item){
                var title = item.querySelector('strong').textContent.toLowerCase();
                var deptTxt = h2.textContent.toLowerCase();
                var locTxt = item.querySelector('.wjb-job-location') ? item.querySelector('.wjb-job-location').textContent : '';
                var country = '';
                var city = '';
                if (locTxt.includes(',')) {
                    var parts = locTxt.split(',');
                    country = parts[parts.length-1].trim();
                    city = parts[0].trim();
                } else {
                    country = locTxt.trim();
                }
                var visible = true;
                if (dept && deptTxt !== dept) visible = false;
                if (locValue) {
                    if (locValue === 'Remote') {
                        if (!/remote/i.test(locTxt)) visible = false;
                    } else if (locValue.indexOf('|') !== -1) {
                        var split = locValue.split('|');
                        var selCountry = split[0].trim();
                        var selCity = split[1].trim();
                        if (country !== selCountry || city !== selCity) visible = false;
                    } else {
                        if (country !== locValue.trim()) visible = false;
                    }
                }
                if(query && !title.includes(query)) visible = false;
                item.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });
            h2.style.display = anyVisible ? '' : 'none';
            ul.style.display = anyVisible ? '' : 'none';
        });
    }
    if (deptSelect) deptSelect.addEventListener('change', filterJobs);
    if (locSelect) locSelect.addEventListener('change', filterJobs);
    if (searchInput) searchInput.addEventListener('input', filterJobs);

    // --- Job detail logic with URL parameter ---
    function showJobDetail(shortcode, pushUrl = true) {
        var detailDiv = document.getElementById('wjb-job-detail');
        if (!shortcode) return;
        detailDiv.innerHTML = '<p>Loading job details...</p>';
        detailDiv.style.display = 'block';
        document.querySelectorAll('.wjb-job-list').forEach(function(ul){ ul.style.display = 'none'; });
        document.querySelectorAll('.wjb-dept-title').forEach(function(h2){ h2.style.display = 'none'; });

        // Update URL
        if (pushUrl) {
            var url = new URL(window.location);
            url.searchParams.set('job', shortcode);
            window.history.replaceState({}, '', url);
        }

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=wjb_fetch_job_detail&shortcode=' + shortcode)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    detailDiv.innerHTML = '<p>Unable to load job details. Please try again later.</p>';
                } else {
                    var html = '<div class="wjb-job-detail-inner">' +
                        '<a href="#" class="wjb-back-to-list">&larr; Back to jobs</a>' +
                        '<h2>' + data.title + '</h2>' +
                        '<div class="wjb-job-desc">' + data.description + '</div>' +
                        '<div id="wjb-application-form-container"></div>' +
                        '</div>';
                    detailDiv.innerHTML = html;

                    detailDiv.querySelector('.wjb-back-to-list').addEventListener('click', function(e) {
                        e.preventDefault();
                        detailDiv.style.display = 'none';
                        document.querySelectorAll('.wjb-job-list').forEach(function(ul){ ul.style.display = ''; });
                        document.querySelectorAll('.wjb-dept-title').forEach(function(h2){ h2.style.display = ''; });
                        var url = new URL(window.location);
                        url.searchParams.delete('job');
                        window.history.replaceState({}, '', url);
                    });

                    // --- SHOW FORM AUTOMATICALLY ---
                    loadWJBApplicationForm(shortcode);
                }
            })
            .catch(function() {
                detailDiv.innerHTML = '<p>Unable to load job details. Please try again later.</p>';
            });
    }

    function loadWJBApplicationForm(shortcode) {
        var $container = jQuery('#wjb-application-form-container');
        $container.html('<p>Loading application form…</p>');
        jQuery.post(wjb_ajax.ajax_url, {
            action: 'wjb_get_application_form',
            nonce: wjb_ajax.nonce,
            shortcode: shortcode
        }, function(response){
            if (response.success) {
                $container.html(renderWJBForm(response.data, shortcode));
            } else {
                $container.html('<p>Error loading form: ' + response.data + '</p>');
            }
        });
    }

    // Render form (with validation and type/file checks)
    function renderWJBForm(form, shortcode) {
        let html = '<form id="wjb-application-form" enctype="multipart/form-data">';
        // Always add required Name/Email fields!
        html += '<div class="wjb-field">';
        html += '<label>Full Name *</label>';
        html += '<input type="text" name="name" required>';
        html += '</div>';
        html += '<div class="wjb-field">';
        html += '<label>Email *</label>';
        html += '<input type="email" name="email" required>';
        html += '</div>';
        if (form.form_fields) {
            form.form_fields.forEach(function(field) {
                // Skip if field.key is 'name' or 'email'
                if (field.key === 'name' || field.key === 'email') return;
                html += '<div class="wjb-field">';
                html += '<label>' + field.label + (field.required ? ' *' : '') + '</label>';
                if (field.type === 'string') {
                    html += '<input type="text" name="' + field.key + '" ' + (field.required ? 'required ' : '') + (field.max_length ? 'maxlength="' + field.max_length + '" ' : '') + '>';
                }
                else if (field.type === 'free_text') {
                    html += '<textarea name="' + field.key + '" ' + (field.required ? 'required ' : '') + (field.max_length ? 'maxlength="' + field.max_length + '" ' : '') + '></textarea>';
                }
                else if (field.type === 'file') {
                    let accept = '';
                    if (field.supported_file_types) {
                        accept = field.supported_file_types.map(function(ext){return '.'+ext;}).join(',');
                    }
                    html += '<input type="file" name="' + field.key + '" ' + (field.required ? 'required ' : '') + (accept ? 'accept="'+accept+'" ' : '') + (field.max_file_size ? 'data-maxsize="'+field.max_file_size+'"' : '') + '>';
                    if (field.max_file_size) {
                        html += '<small>Max file size: ' + Math.round(field.max_file_size/1024/1024) + 'MB</small><br>';
                    }
                    if (accept) {
                        html += '<small>Allowed file types: ' + accept.replace(/\./g, '').replace(/,/g, ', ') + '</small>';
                    }
                }
                else if (field.type === 'date') {
                    html += '<input type="date" name="' + field.key + '" ' + (field.required ? 'required' : '') + '>';
                }
                else if (field.type === 'boolean') {
                    html += '<input type="checkbox" name="' + field.key + '" value="1"> Yes';
                }
                else if (field.type === 'complex' && field.multiple && Array.isArray(field.fields)) {
                    html += '<div class="wjb-complex-group" data-key="' + field.key + '">';
                    html += renderComplexFields(field.fields, field.key, 0);
                    html += '<button type="button" class="wjb-add-group" data-key="' + field.key + '">Add more</button>';
                    html += '</div>';
                }
                html += '</div>';
            });
        }
        html += '<input type="hidden" name="shortcode" value="' + shortcode + '">';
        html += '<button type="submit">Submit Application</button>';
        html += '<div class="wjb-form-msg"></div>';
        html += '</form>';
        return html;
    }

    function renderComplexFields(fields, groupKey, idx) {
        let html = '<div class="wjb-complex-entry" data-idx="' + idx + '">';
        fields.forEach(function(subfield) {
            html += '<label>' + subfield.label + (subfield.required ? ' *' : '') + '</label>';
            if (subfield.type === 'string' || subfield.type === 'date') {
                html += '<input type="' + (subfield.type === 'date' ? 'date' : 'text') + '" name="' + groupKey + '['+idx+']['+subfield.key+']" ' + (subfield.required ? 'required ' : '') + (subfield.max_length ? 'maxlength="' + subfield.max_length + '" ' : '') + '>';
            } else if (subfield.type === 'free_text') {
                html += '<textarea name="' + groupKey + '['+idx+']['+subfield.key+']" ' + (subfield.required ? 'required ' : '') + (subfield.max_length ? 'maxlength="' + subfield.max_length + '" ' : '') + '></textarea>';
            } else if (subfield.type === 'boolean') {
                html += '<input type="checkbox" name="' + groupKey + '['+idx+']['+subfield.key+']" value="1"> Yes';
            }
        });
        html += '<button type="button" class="wjb-remove-group" style="margin-top:4px;">Remove</button>';
        html += '</div>';
        return html;
    }

    jQuery(document).off('click', '.wjb-add-group').on('click', '.wjb-add-group', function() {
        let groupKey = jQuery(this).data('key');
        let $group = jQuery(this).closest('.wjb-complex-group');
        let idx = $group.find('.wjb-complex-entry').length;
        let fields = null;
        if (window.wjbLastFormData && window.wjbLastFormData.form_fields) {
            window.wjbLastFormData.form_fields.forEach(function(f){
                if (f.key == groupKey && f.fields) fields = f.fields;
            });
        }
        if (fields) {
            $group.append(renderComplexFields(fields, groupKey, idx));
        }
    });
    jQuery(document).off('click', '.wjb-remove-group').on('click', '.wjb-remove-group', function() {
        jQuery(this).closest('.wjb-complex-entry').remove();
    });

    window.wjbLastFormData = null;

    jQuery(document).off('submit', '#wjb-application-form').on('submit', '#wjb-application-form', function(e){
        var $form = jQuery(this);
        // === ANTI-DOUBLE-SUBMIT PATCH START ===
        var $submitBtn = $form.find('button[type="submit"]');
        if ($submitBtn.prop('disabled')) return false;
        $submitBtn.prop('disabled', true);
        // === PATCH END ===
        var valid = true, msg = '';
        $form.find('input[type="file"]').each(function(){
            if (this.files.length) {
                var file = this.files[0];
                var maxsize = jQuery(this).data('maxsize');
                if (maxsize && file.size > maxsize) {
                    valid = false;
                    msg = 'File "' + file.name + '" is too large.';
                }
                var accept = jQuery(this).attr('accept');
                if (accept) {
                    var allowed = accept.replace(/\./g,'').split(',');
                    var ext = file.name.split('.').pop().toLowerCase();
                    if (allowed.indexOf(ext) === -1) {
                        valid = false;
                        msg = 'File "' + file.name + '" type not allowed.';
                    }
                }
            }
        });
        if (!valid) {
            $form.find('.wjb-form-msg').html('<span style="color:red">'+msg+'</span>');
            $submitBtn.prop('disabled', false);
            e.preventDefault();
            return false;
        }
        e.preventDefault();
        var form = $form[0];
        var data = new FormData(form);
        data.append('action', 'wjb_submit_application');
        data.append('nonce', wjb_ajax.nonce);
        var $msg = $form.find('.wjb-form-msg');
        $msg.html('Submitting…');
        jQuery.ajax({
            url: wjb_ajax.ajax_url,
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function(response) {
                $submitBtn.prop('disabled', false);
                if (response.success) {
                    $msg.html('<span style="color:green">' + response.data + '</span>');
                    form.reset();
                } else {
                    $msg.html('<span style="color:red">Error: ' + response.data + '</span>');
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false);
                $msg.html('<span style="color:red">Submission failed. Try again.</span>');
            }
        });
    });

    document.querySelectorAll('.wjb-view-detail-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var li = this.closest('.wjb-job-item');
            var shortcode = li.getAttribute('data-shortcode');
            showJobDetail(shortcode, true);
        });
    });

    // --- On page load, open detail if ?job=SHORTCODE present ---
    var params = new URLSearchParams(window.location.search);
    var jobParam = params.get('job');
    if (jobParam) {
        showJobDetail(jobParam, false);
        setTimeout(function(){
            var el = document.getElementById('wjb-job-detail');
            if (el) el.scrollIntoView({behavior: 'smooth'});
        }, 300);
    }
});
</script>
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
    $apply_url = isset($job['application_url']) ? $job['application_url'] : 'https://' . WJB_WORKABLE_SUBDOMAIN . '.workable.com/j/' . $shortcode;
    wp_send_json([
        'title' => $job['title'],
        'description' => $desc,
        'apply_url' => $apply_url,
    ]);
}
?>
