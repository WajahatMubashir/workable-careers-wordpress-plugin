jQuery(document).ready(function ($) {

/* ============ ACCESSIBILITY HELPER ============ */
function enhanceAccessibility() {
    $('.wjbfw-view-detail-btn').each(function () {
        const $link   = $(this);
        const $title  = $link.find('strong');
        const jobText = $.trim($title.text());

        // Link behaves as a button
        $link.attr('role', 'button')
                .attr('aria-label', 'View details about ' + jobText);

        // Job title acts as a heading
        $title.attr({
            role:       'heading',
            'aria-level': 3    // <h3>-equivalent
        });
    });
}

/* ============ FILTER LOCK / UNLOCK ============ */
function toggleFilters(disabled) {
    const $inputs = $('#wjbfw-job-search, #wjbfw-filter-department, #wjbfw-filter-location');
    $inputs.prop('disabled', disabled)
            .toggleClass('wjbfw-disabled', disabled);
}

// First paint
toggleFilters(new URLSearchParams(window.location.search).has('job'));
enhanceAccessibility(); 

// --- Job filter logic ---
$('#wjbfw-filter-department, #wjbfw-filter-location').on('change', filterJobs);
$('#wjbfw-job-search').on('input', filterJobs);

function filterJobs() {

const locFilter  = $('#wjbfw-filter-location').val();          // "" / Country / City / Remote
const deptFilter = $('#wjbfw-filter-department').val() || "";  // whatever your plugin already has
const searchText = $('#wjbfw-filter-search').val().toLowerCase();

$('.wjbfw-job-item').each(function () {

    const $item     = $(this);
    const isRemote  = $item.data('remote') == 1 || $item.data('remote') === '1';
    const itemCity  = ($item.data('city')     || '').toString();
    const itemCountry = ($item.data('country')|| '').toString();
    const itemDept  = ($item.data('department') || '').toString();   // already printed in HTML
    const itemTitle = $item.find('.wjbfw-job-title').text().toLowerCase();

    let visible = true;

    /* ── Location logic ─────────────────────────────────────────── */
    if (locFilter) {
        if (locFilter === 'Remote') {
            visible = isRemote;                       // show *all* remote roles
        } else {
            // user picked a Country or a City
            const locMatch =
                    itemCountry === locFilter           // matches country
                || itemCity    === locFilter;          // or matches city

            // Remote roles also belong to their city / country
            visible = locMatch;
        }
    }

    /* ── Department filter (already exists in the plugin) ── */
    if (visible && deptFilter) {
        visible = (itemDept === deptFilter);
    }

    /* ── Free‑text search (already exists) ──────────────── */
    if (visible && searchText) {
        visible = itemTitle.indexOf(searchText) !== -1;
    }

    /* ── show / hide ─────────────────────────────────────── */
    $item.toggle(visible);
});
}
/* trigger filterJobs() on any change, as plugin already does */
$('#wjbfw-filter-location, #wjbfw-filter-department, #wjbfw-filter-search').on('change keyup', filterJobs);

// --- Job detail logic ---
$(document).on('click', '.wjbfw-view-detail-btn', function (e) {
    e.preventDefault();
    var shortcode = $(this).closest('.wjbfw-job-item').data('shortcode');
    showJobDetail(shortcode, true);
});

function showJobDetail(shortcode, pushUrl = true) {
    var $detailDiv = $('#wjbfw-job-detail');
    if (!shortcode) return;

    $detailDiv.html('<p>Loading job details...</p>').show();
    $('.wjbfw-job-list, .wjbfw-dept-title').hide();
    toggleFilters(true);

    // Update URL
    if (pushUrl) {
        var url = new URL(window.location);
        url.searchParams.set('job', shortcode);
        window.history.replaceState({}, '', url);
    }

    $.get(wjbfw_ajax.ajax_url, {
        action: 'wjbfw_fetch_job_detail',
        shortcode: shortcode
    }, function (data) {
        if (data.error) {
            $detailDiv.html('<p>Unable to load job details. Please try again later.</p>');
        } else {
            var html = '<div class="wjbfw-job-detail-inner">' +
                '<a href="#" class="wjbfw-back-to-list">&larr; Back to jobs</a>' +
                '<h2>' + data.title + '</h2>' +
                '<div class="wjbfw-job-desc">' + data.description + '</div>' +
                '<div id="wjbfw-application-form-container"></div>' +
                '</div>';
            $detailDiv.html(html);

            $detailDiv.find('.wjbfw-back-to-list').on('click', function (e) {
                e.preventDefault();
                $detailDiv.hide();
                $('.wjbfw-job-list, .wjbfw-dept-title').show();
                toggleFilters(false);

                var url = new URL(window.location);
                url.searchParams.delete('job');
                window.history.replaceState({}, '', url);

                // Re-run a11y patch in case elements were re-rendered
                enhanceAccessibility();
            });

            loadWJBApplicationForm(shortcode);
        }
    }).fail(function () {
        $detailDiv.html('<p>Unable to load job details. Please try again later.</p>');
    });
}

function loadWJBApplicationForm(shortcode) {
    var $container = $('#wjbfw-application-form-container');
    $container.html('<p>Loading application form…</p>');
    $.post(wjbfw_ajax.ajax_url, {
        action: 'wjbfw_get_application_form',
        nonce:  wjbfw_ajax.nonce,
        shortcode: shortcode
    }, function (response) {
        if (response.success) {
            $container.html(renderWJBForm(response.data, shortcode));
        } else {
            $container.html('<p>Error loading form: ' + response.data + '</p>');
        }
    });
}

function renderWJBForm(form, shortcode) {
    let html  = '<form id="wjbfw-application-form" enctype="multipart/form-data">';
    html     += '<div class="wjbfw-field"><label>Full Name *</label><input type="text" name="name" required></div>';
    html     += '<div class="wjbfw-field"><label>Email *</label><input type="email" name="email" required></div>';

    if (form.form_fields) {
        form.form_fields.forEach(function (field) {
            if (field.key === 'name' || field.key === 'email') return;

            html += '<div class="wjbfw-field"><label>' + field.label + (field.required ? ' *' : '') + '</label>';

            if (field.type === 'string') {
                html += '<input type="text" name="' + field.key + '" ' +
                        (field.required ? 'required ' : '') +
                        (field.max_length ? 'maxlength="' + field.max_length + '" ' : '') + '>';
            } else if (field.type === 'free_text') {
                html += '<textarea name="' + field.key + '" ' +
                        (field.required ? 'required ' : '') +
                        (field.max_length ? 'maxlength="' + field.max_length + '" ' : '') + '></textarea>';
            } else if (field.type === 'file') {
                let accept = '';
                if (field.supported_file_types) {
                    accept = field.supported_file_types.map(ext => '.' + ext).join(',');
                }
                html += '<input type="file" name="' + field.key + '" ' +
                        (field.required ? 'required ' : '') +
                        (accept ? 'accept="' + accept + '" ' : '') +
                        (field.max_file_size ? 'data-maxsize="' + field.max_file_size + '" ' : '') + '>';
                if (field.max_file_size) {
                    html += '<small>Max file size: ' + Math.round(field.max_file_size / 1024 / 1024) + 'MB</small><br>';
                }
                if (accept) {
                    html += '<small>Allowed file types: ' + accept.replace(/\./g, '').replace(/,/g, ', ') + '</small>';
                }
            } else if (field.type === 'date') {
                html += '<input type="date" name="' + field.key + '" ' + (field.required ? 'required' : '') + '>';
            } else if (field.type === 'boolean') {
                html += '<input type="checkbox" name="' + field.key + '" value="1"> Yes';
            }
            html += '</div>';
        });
    }

    html += '<input type="hidden" name="shortcode" value="' + shortcode + '">';
    html += '<button type="submit">Submit Application</button>';
    html += '<div class="wjbfw-form-msg"></div></form>';
    return html;
}

// ==== ANTI-DOUBLE-SUBMIT PATCH ====
$(document).off('submit', '#wjbfw-application-form').on('submit', '#wjbfw-application-form', function (e) {
    var $form      = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    if ($submitBtn.prop('disabled')) return false;
    $submitBtn.prop('disabled', true);

    var valid = true, msg = '';
    $form.find('input[type="file"]').each(function () {
        if (this.files.length) {
            var file     = this.files[0];
            var maxsize  = $(this).data('maxsize');
            if (maxsize && file.size > maxsize) {
                valid = false; msg = 'File "' + file.name + '" is too large.';
            }
            var accept = $(this).attr('accept');
            if (accept) {
                var allowed = accept.replace(/\./g, '').split(',');
                var ext     = file.name.split('.').pop().toLowerCase();
                if (allowed.indexOf(ext) === -1) {
                    valid = false; msg = 'File "' + file.name + '" type not allowed.';
                }
            }
        }
    });

    if (!valid) {
        $form.find('.wjbfw-form-msg').html('<span style="color:red">' + msg + '</span>');
        $submitBtn.prop('disabled', false);
        e.preventDefault();
        return false;
    }

    e.preventDefault();
    var data = new FormData($form[0]);
    data.append('action', 'wjbfw_submit_application');
    data.append('nonce',  wjbfw_ajax.nonce);

    var $msg = $form.find('.wjbfw-form-msg');
    $msg.html('Submitting…');

    $.ajax({
        url:         wjbfw_ajax.ajax_url,
        type:        'POST',
        data:        data,
        processData: false,
        contentType: false,
        success: function (response) {
            $submitBtn.prop('disabled', false);
            if (response.success) {
                $msg.html('<span style="color:green">' + response.data + '</span>');
                $form[0].reset();
            } else {
                $msg.html('<span style="color:red">Error: ' + response.data + '</span>');
            }
        },
        error: function () {
            $submitBtn.prop('disabled', false);
            $msg.html('<span style="color:red">Submission failed. Try again.</span>');
        }
    });
});

// --- On page load, open detail if ?job=SHORTCODE present ---
const params   = new URLSearchParams(window.location.search);
const jobParam = params.get('job');
if (jobParam) {
    showJobDetail(jobParam, false);
    setTimeout(() => {
        var el = document.getElementById('wjbfw-job-detail');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    }, 300);
}

    /* ====== EXTRA KEYBOARD SUPPORT FOR “BUTTON” LINKS ====== */
    $(document).on('keydown', '.wjbfw-view-detail-btn[role="button"]', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).trigger('click');
    }
    });
    
});
