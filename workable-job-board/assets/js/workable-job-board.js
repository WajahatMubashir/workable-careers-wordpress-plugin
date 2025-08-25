// /assets/js/workable-job-board.js

jQuery(document).ready(function ($) {

    // ========== POPULATE DEPARTMENT DROPDOWN ==========
    function populateDepartments() {
        var deptSelect = document.getElementById('wjb-filter-department');
        if (deptSelect) {
            // Remove all except first ("All Departments")
            deptSelect.options.length = 1;
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
    }

    // Call this once DOM is ready (and whenever jobs are re-rendered)
    populateDepartments();

    // ========== FILTERING ==========
    $('#wjb-filter-department, #wjb-filter-location').on('change', filterJobs);
    $('#wjb-job-search').on('input', filterJobs);

    function filterJobs() {
        var dept = $('#wjb-filter-department').val().toLowerCase();
        var locValue = $('#wjb-filter-location').val();
        var query = $('#wjb-job-search').val().toLowerCase();
        $('.wjb-dept-title').each(function () {
            var $h2 = $(this);
            var $ul = $h2.next('.wjb-job-list');
            var anyVisible = false;
            $ul.find('.wjb-job-item').each(function () {
                var $item = $(this);
                var title = $item.find('strong').text().toLowerCase();
                var deptTxt = $h2.text().toLowerCase();
                var locTxt = $item.find('.wjb-job-location').text();
                var country = '', city = '';
                if (locTxt.indexOf(',') !== -1) {
                    var parts = locTxt.split(',');
                    country = parts[parts.length - 1].trim();
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
                        if (country !== split[0].trim() || city !== split[1].trim()) visible = false;
                    } else {
                        if (country !== locValue.trim()) visible = false;
                    }
                }
                if (query && title.indexOf(query) === -1) visible = false;
                $item.toggle(visible);
                if (visible) anyVisible = true;
            });
            $h2.toggle(anyVisible);
            $ul.toggle(anyVisible);
        });
    }

    // ========== JOB DETAIL POPUP ==========
    $(document).on('click', '.wjb-view-detail-btn', function (e) {
        e.preventDefault();
        var shortcode = $(this).closest('.wjb-job-item').data('shortcode');
        showJobDetail(shortcode, true);
    });

    function showJobDetail(shortcode, pushUrl = true) {
        var $detailDiv = $('#wjb-job-detail');
        if (!shortcode) return;
        $detailDiv.html('<p>Loading job details...</p>').show();
        $('.wjb-job-list, .wjb-dept-title').hide();

        // Update URL
        if (pushUrl) {
            var url = new URL(window.location);
            url.searchParams.set('job', shortcode);
            window.history.replaceState({}, '', url);
        }

        $.get(wjb_ajax.ajax_url, {
            action: 'wjb_fetch_job_detail',
            shortcode: shortcode
        }, function (data) {
            if (data.error) {
                $detailDiv.html('<p>Unable to load job details. Please try again later.</p>');
            } else {
                var html = '<div class="wjb-job-detail-inner">' +
                    '<a href="#" class="wjb-back-to-list">&larr; Back to jobs</a>' +
                    '<h2>' + data.title + '</h2>' +
                    '<div class="wjb-job-desc">' + data.description + '</div>' +
                    '<div id="wjb-application-form-container"></div>' +
                    '</div>';
                $detailDiv.html(html);

                $detailDiv.find('.wjb-back-to-list').on('click', function (e) {
                    e.preventDefault();
                    $detailDiv.hide();
                    $('.wjb-job-list, .wjb-dept-title').show();
                    var url = new URL(window.location);
                    url.searchParams.delete('job');
                    window.history.replaceState({}, '', url);
                });

                loadWJBApplicationForm(shortcode);
            }
        }).fail(function () {
            $detailDiv.html('<p>Unable to load job details. Please try again later.</p>');
        });
    }

    // ========== LOAD APPLICATION FORM ==========
    function loadWJBApplicationForm(shortcode) {
        var $container = $('#wjb-application-form-container');
        $container.html('<p>Loading application form…</p>');
        $.post(wjb_ajax.ajax_url, {
            action: 'wjb_get_application_form',
            nonce: wjb_ajax.nonce,
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
        let html = '<form id="wjb-application-form" enctype="multipart/form-data">';
        html += '<div class="wjb-field"><label>Full Name *</label><input type="text" name="name" required></div>';
        html += '<div class="wjb-field"><label>Email *</label><input type="email" name="email" required></div>';
        if (form.form_fields) {
            form.form_fields.forEach(function (field) {
                if (field.key === 'name' || field.key === 'email') return;
                html += '<div class="wjb-field">';
                html += '<label>' + field.label + (field.required ? ' *' : '') + '</label>';
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
                        accept = field.supported_file_types.map(function (ext) { return '.' + ext; }).join(',');
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
        html += '<div class="wjb-form-msg"></div>';
        html += '</form>';
        return html;
    }

    // ==== ANTI-DOUBLE-SUBMIT PATCH ====
    $(document).off('submit', '#wjb-application-form').on('submit', '#wjb-application-form', function (e) {
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        if ($submitBtn.prop('disabled')) return false;
        $submitBtn.prop('disabled', true);

        var valid = true, msg = '';
        $form.find('input[type="file"]').each(function () {
            if (this.files.length) {
                var file = this.files[0];
                var maxsize = $(this).data('maxsize');
                if (maxsize && file.size > maxsize) {
                    valid = false;
                    msg = 'File "' + file.name + '" is too large.';
                }
                var accept = $(this).attr('accept');
                if (accept) {
                    var allowed = accept.replace(/\./g, '').split(',');
                    var ext = file.name.split('.').pop().toLowerCase();
                    if (allowed.indexOf(ext) === -1) {
                        valid = false;
                        msg = 'File "' + file.name + '" type not allowed.';
                    }
                }
            }
        });
        if (!valid) {
            $form.find('.wjb-form-msg').html('<span style="color:red">' + msg + '</span>');
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
        $.ajax({
            url: wjb_ajax.ajax_url,
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                $submitBtn.prop('disabled', false);
                if (response.success) {
                    $msg.html('<span style="color:green">' + response.data + '</span>');
                    form.reset();
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
    var params = new URLSearchParams(window.location.search);
    var jobParam = params.get('job');
    if (jobParam) {
        showJobDetail(jobParam, false);
        setTimeout(function () {
            var el = document.getElementById('wjb-job-detail');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        }, 300);
    }
});
