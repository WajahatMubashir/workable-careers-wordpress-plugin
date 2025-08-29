/* Front-end filtering & detail logic for Job Board for Workable */

// Toggle filter enable/disable state
function toggleFilters(disabled) {
    const inputs = jQuery('#wjbfw-job-search, #wjbfw-filter-department, #wjbfw-filter-location');
    inputs.prop('disabled', disabled).toggleClass('wjbfw-disabled', disabled);
}

document.addEventListener('DOMContentLoaded', function () {

	const deptSel  = document.getElementById('wjbfw-filter-department');
	const locSel   = document.getElementById('wjbfw-filter-location');
	const searchIn = document.getElementById('wjbfw-job-search');

	// Initialize filters state
	toggleFilters(new URLSearchParams(window.location.search).has('job'));

	/* populate department dropdown */
	if (deptSel) {
		const seen = new Set();
		document.querySelectorAll('.wjbfw-dept-title').forEach(h2 => {
			const t = h2.textContent.trim();
			if (t && !seen.has(t)) {
				seen.add(t);
				const opt = document.createElement('option');
				opt.value = t; opt.textContent = t;
				deptSel.appendChild(opt);
			}
		});
	}

	function filterJobs() {
		const deptVal = deptSel ? deptSel.value.trim() : '';
		const locVal  = locSel  ? locSel.value.trim()  : '';
		const q       = searchIn ? searchIn.value.toLowerCase() : '';

		document.querySelectorAll('.wjbfw-dept-title').forEach(h2 => {
			const ul = h2.nextElementSibling;
			let any  = false;

			ul.querySelectorAll('.wjbfw-job-item').forEach(item => {

				const title = item.querySelector('.wjbfw-job-title').textContent.toLowerCase();
				const itemDept    = item.dataset.department || '';
				const itemCountry = item.dataset.country    || '';
				const itemCity    = item.dataset.city       || '';
				const isRemote    = item.dataset.remote === '1';

				let visible = true;

				/* location filter */
				if (locVal) {
					if (locVal === 'Remote') {
						visible = isRemote;
					} else {
						const locMatch = (itemCountry === locVal) || (itemCity === locVal);
						visible = locMatch;
					}
				}

				/* department filter */
				if (visible && deptVal)  visible = (itemDept === deptVal);

				/* search */
				if (visible && q)        visible = title.includes(q);

				item.style.display = visible ? '' : 'none';
				if (visible) any = true;
			});

			h2.style.display = any ? '' : 'none';
			ul.style.display = any ? '' : 'none';
		});
	}

	['change','keyup'].forEach(ev => {
		if (deptSel)  deptSel.addEventListener(ev, filterJobs);
		if (locSel)   locSel.addEventListener(ev,  filterJobs);
		if (searchIn) searchIn.addEventListener(ev,filterJobs);
	});

	/* detail view --------------------------------------------------- */
	function showJobDetail(shortcode, push = true) {
		if (!shortcode) return;
		const detail = document.getElementById('wjbfw-job-detail');
		detail.innerHTML = '<p>Loading job details…</p>';
		detail.style.display = 'block';
		document.querySelectorAll('.wjbfw-job-list,.wjbfw-dept-title').forEach(el => el.style.display='none');
		toggleFilters(true);

		if (push) {
			const url = new URL(window.location);
			url.searchParams.set('job', shortcode);
			window.history.replaceState({},'',url);
		}

		const fetchUrl = new URL(wjbfw_ajax.ajax_url);
		fetchUrl.searchParams.set('action', 'wjbfw_fetch_job_detail');
		fetchUrl.searchParams.set('shortcode', shortcode);
		fetchUrl.searchParams.set('nonce', wjbfw_ajax.nonce);

		fetch(fetchUrl.toString())
			.then(r => r.json())
			.then(data => {
			if (data.error || !data.success) {
				detail.innerHTML = '<p>Unable to load job details. Please try again later.</p>';
				return;
			}
			detail.innerHTML =
				'<div class=\"wjbfw-job-detail-inner\">' +
				'<a href=\"#\" class=\"wjbfw-back-to-list\">&larr; Back to jobs</a>' +
				'<h2>'+data.data.title+'</h2>' +
				'<div class=\"wjbfw-job-desc\">'+data.data.description+'</div>' +
				'<div id=\"wjbfw-application-form-container\"></div>' +
				'</div>';

			detail.querySelector('.wjbfw-back-to-list').addEventListener('click', e => {
				e.preventDefault();
				detail.style.display='none';
				document.querySelectorAll('.wjbfw-job-list,.wjbfw-dept-title').forEach(el => el.style.display='');
				toggleFilters(false);
				const url = new URL(window.location);
				url.searchParams.delete('job');
				window.history.replaceState({},'',url);
			});

			loadWJBApplicationForm(shortcode);
		})
			.catch(() => detail.innerHTML='<p>Error loading job details.</p>');
	}

	function loadWJBApplicationForm(sc) {
		const c = jQuery('#wjbfw-application-form-container');
		c.html('<p>Loading application form…</p>');
		jQuery.post(wjbfw_ajax.ajax_url,{
			action:'wjbfw_get_application_form',
			nonce: wjbfw_ajax.nonce,
			shortcode: sc
		},res=>{
			if (res.success) c.html(renderWJBForm(res.data, sc));
			else             c.html('<p>Error: '+res.data+'</p>');
		});
	}

	/* click handler */
	document.querySelectorAll('.wjbfw-view-detail-btn').forEach(btn=>{
		btn.addEventListener('click',e=>{
			e.preventDefault();
			const sc = btn.closest('.wjbfw-job-item').dataset.shortcode;
			showJobDetail(sc,true);
		});
	});

	/* open detail if ?job= param */
	const urlJob = new URLSearchParams(window.location.search).get('job');
	if (urlJob) {
		showJobDetail(urlJob,false);
		setTimeout(()=>{ const el=document.getElementById('wjbfw-job-detail'); if(el) el.scrollIntoView({behavior:'smooth'}); },300);
	}
});

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

// Form submission handler
jQuery(document).ready(function($) {
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
});