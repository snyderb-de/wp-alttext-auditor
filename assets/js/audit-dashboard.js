/**
 * WP Alt Text Updater - Audit Dashboard JavaScript
 *
 * Handles AJAX interactions for the audit dashboard including:
 * - Chunked batch scanning of posts and media library
 * - Real-time progress tracking with animated progress bars
 * - Statistics loading and caching
 * - User attribution display
 * - Quick-edit functionality for inline alt-text updates
 * - CSV export with filter support
 * - Automatic daily scanning toggle
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';

    /**
     * Scanning state flag to prevent concurrent scans
     * @type {boolean}
     */
    let isScanning = false;

    /**
     * Current scan type ('content' or 'media')
     * @type {string|null}
     */
    let currentScanType = null;

    /**
     * Scan start time for ETA calculation
     * @type {number|null}
     */
    let scanStartTime = null;

    /**
     * Last item being scanned
     * @type {string}
     */
    let lastScannedItem = '';

    /**
     * Initialize dashboard on page load
     * Loads statistics, user attribution, and binds all event handlers
     */
    function initDashboard() {
        // Load statistics if on overview tab
        if ($('#audit-stats-cards').length > 0) {
            loadStatistics();
        }

        // Load user attribution if on users tab
        if ($('#audit-user-results').length > 0 && $('.audit-users-tab').length > 0) {
            loadUserAttribution();
        }

        // Bind event handlers
        bindScanButtons();
        bindCancelButton();
        bindClearCacheButton();
        bindQuickEditButtons();
        bindExportButton();
        bindCronToggle();
        bindReportButton();
    }

    /**
     * Bind scan button click handlers
     * Sets up event listeners for content and media library scan buttons
     */
    function bindScanButtons() {
        $('#scan-content-btn').on('click', function() {
            startScan('content');
        });

        $('#scan-drafts-btn').on('click', function() {
            startScan('drafts');
        });

        $('#scan-media-btn').on('click', function() {
            startScan('media');
        });
    }

    /**
     * Bind cancel scan button handler
     */
    function bindCancelButton() {
        $('#cancel-scan-btn').on('click', function() {
            cancelScan();
        });
    }

    /**
     * Bind clear cache button handler
     */
    function bindClearCacheButton() {
        $('#clear-cache-btn').on('click', function() {
            clearCache();
        });
    }

    /**
     * Bind quick-edit button handlers with event delegation
     * Handles show/hide edit mode and save/cancel actions for inline editing
     */
    function bindQuickEditButtons() {
        // Use event delegation for dynamically loaded content
        $(document).on('click', '.audit-quick-edit-trigger', function() {
            var $row = $(this).closest('td');

            // Disable all other edit buttons while one is being edited
            $('.audit-quick-edit-trigger').not(this).prop('disabled', true).addClass('disabled');

            $row.find('.audit-alt-text-display').hide();
            $row.find('.audit-alt-text-edit').show();
            $row.find('.audit-quick-edit-input').focus();
        });

        $(document).on('click', '.audit-cancel-quick-edit', function() {
            var $row = $(this).closest('td');
            $row.find('.audit-alt-text-edit').hide();
            $row.find('.audit-alt-text-display').show();
            $row.find('.audit-quick-edit-input').val('');

            // Re-enable all edit buttons
            $('.audit-quick-edit-trigger').prop('disabled', false).removeClass('disabled');
        });

        $(document).on('click', '.audit-save-quick-edit', function() {
            var $row = $(this).closest('td');
            triggerSave($row);
        });

        // Handle Enter key to save
        $(document).on('keydown', '.audit-quick-edit-input', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var $row = $(this).closest('td');
                triggerSave($row);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                $(this).closest('td').find('.audit-cancel-quick-edit').click();
            }
        });

        // Character counter
        $(document).on('input', '.audit-quick-edit-input', function() {
            var length = $(this).val().length;
            var maxLength = 255;
            var remaining = maxLength - length;
            var $counter = $(this).siblings('.audit-char-counter');

            if ($counter.length === 0) {
                $counter = $('<span class="audit-char-counter"></span>');
                $(this).after($counter);
            }

            $counter.text(remaining + ' characters remaining');

            if (remaining < 0) {
                $counter.css('color', '#d63638');
            } else if (remaining < 50) {
                $counter.css('color', '#dba617');
            } else {
                $counter.css('color', '#646970');
            }
        });

        /**
         * Trigger save action for quick edit
         * @param {jQuery} $row - Table row element
         */
        function triggerSave($row) {
            var $display = $row.find('.audit-alt-text-display');
            var attachmentId = $display.data('attachment-id');
            var resultId = $display.data('result-id');
            var altText = $row.find('.audit-quick-edit-input').val().trim();

            if (!altText) {
                alert('Please enter alt text.');
                return;
            }

            saveQuickEdit(attachmentId, resultId, altText, $row);
        }
    }

    /**
     * Save quick-edit alt text via two-step AJAX process
     * First updates WordPress attachment meta, then syncs audit database
     *
     * @param {number} attachmentId - WordPress attachment ID
     * @param {number} resultId - Audit database result ID
     * @param {string} altText - New alt-text value
     * @param {jQuery} $row - jQuery object of the table row
     */
    function saveQuickEdit(attachmentId, resultId, altText, $row) {
        var $edit = $row.find('.audit-alt-text-edit');
        var $spinner = $edit.find('.spinner');
        var $saveBtn = $edit.find('.audit-save-quick-edit');
        var $cancelBtn = $edit.find('.audit-cancel-quick-edit');

        // Disable buttons and show spinner
        $saveBtn.prop('disabled', true);
        $cancelBtn.prop('disabled', true);
        $spinner.addClass('is-active');

        // Single AJAX call - updates both audit record AND media library
        $.ajax({
            url: altTextAuditor.ajax_url,
            type: 'POST',
            data: {
                action: 'alttext_update_audit_record',
                nonce: altTextAuditor.audit_nonce,
                result_id: resultId,
                alt_text: altText
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                $saveBtn.prop('disabled', false);
                $cancelBtn.prop('disabled', false);

                if (response.success) {
                    // Hide edit mode and show success checkmark
                    $edit.hide();
                    var $display = $row.find('.audit-alt-text-display');
                    $display.html('<span class="audit-status has-alt" style="color: #00a32a; font-weight: 600;"><span class="dashicons dashicons-yes-alt"></span> Saved!</span>').show();

                    // Re-enable all edit buttons
                    $('.audit-quick-edit-trigger').prop('disabled', false).removeClass('disabled');

                    // Wait a moment, then fade out and remove row
                    setTimeout(function() {
                        $row.closest('tr').fadeOut(400, function() {
                            $(this).remove();

                            // Show message
                            showSuccess('Alt text updated successfully! Refreshing...');

                            // Reload page after 1 second to update counts
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        });
                    }, 800);
                } else {
                    // Re-enable all edit buttons on error
                    $('.audit-quick-edit-trigger').prop('disabled', false).removeClass('disabled');
                    showError('Failed to update alt text: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                $spinner.removeClass('is-active');
                $saveBtn.prop('disabled', false);
                $cancelBtn.prop('disabled', false);
                // Re-enable all edit buttons on error
                $('.audit-quick-edit-trigger').prop('disabled', false).removeClass('disabled');
                showError('Network error while updating alt text.');
            }
        });
    }


    /**
     * Bind export CSV button handler
     */
    function bindExportButton() {
        $(document).on('click', '#export-csv-btn', function(e) {
            e.preventDefault();

            // Get current filter parameters from URL
            var urlParams = new URLSearchParams(window.location.search);
            var filters = {
                filter_user: urlParams.get('filter_user') || '',
                filter_content_type: urlParams.get('filter_content_type') || '',
                filter_post_type: urlParams.get('filter_post_type') || '',
                filter_search: urlParams.get('filter_search') || ''
            };

            // Build export URL with filters
            var exportUrl = altTextAuditor.ajax_url +
                '?action=alttext_audit_export' +
                '&nonce=' + altTextAuditor.audit_nonce +
                '&filter_user=' + encodeURIComponent(filters.filter_user) +
                '&filter_content_type=' + encodeURIComponent(filters.filter_content_type) +
                '&filter_post_type=' + encodeURIComponent(filters.filter_post_type) +
                '&filter_search=' + encodeURIComponent(filters.filter_search);

            // Trigger download by opening URL in new window
            window.location.href = exportUrl;
        });
    }

    /**
     * Bind cron toggle checkbox handler
     */
    function bindCronToggle() {
        $('#cron-enabled-checkbox').on('change', function() {
            var enabled = $(this).is(':checked');
            var $statusMessage = $('#cron-status-message');

            $.ajax({
                url: altTextAuditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'alttext_toggle_cron',
                    nonce: altTextAuditor.audit_nonce,
                    enabled: enabled ? 'true' : 'false'
                },
                success: function(response) {
                    if (response.success) {
                        $statusMessage.removeClass('error').addClass('success');
                        $statusMessage.text(response.data.message).show();

                        // Reload page after 2 seconds to show updated schedule
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $statusMessage.removeClass('success').addClass('error');
                        $statusMessage.text(response.data.message || 'Error toggling automatic scanning').show();
                        // Revert checkbox state
                        $('#cron-enabled-checkbox').prop('checked', !enabled);
                    }
                },
                error: function() {
                    $statusMessage.removeClass('success').addClass('error');
                    $statusMessage.text('Network error while toggling automatic scanning').show();
                    // Revert checkbox state
                    $('#cron-enabled-checkbox').prop('checked', !enabled);
                }
            });
        });
    }

    /**
     * Bind report generation button handler
     * Generates HTML report on demand via AJAX
     */
    function bindReportButton() {
        $('#generate-report-btn').on('click', function() {
            var $btn = $(this);
            var $statusMessage = $('#report-status-message');
            var originalText = $btn.text();

            // Disable button and show loading
            $btn.prop('disabled', true).text('Generating Report...');
            $statusMessage.hide();

            $.ajax({
                url: altTextAuditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'alttext_generate_report',
                    nonce: altTextAuditor.audit_nonce
                },
                success: function(response) {
                    if (response.success) {
                        $statusMessage.removeClass('error').addClass('success');
                        $statusMessage.html(response.data.message + ' <a href="' + escapeHtml(response.data.report_url) + '" target="_blank">View Report</a>').show();

                        // Reload page after 2 seconds to show new report in list
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $statusMessage.removeClass('success').addClass('error');
                        $statusMessage.text(response.data.message || 'Error generating report').show();
                    }
                },
                error: function() {
                    $statusMessage.removeClass('success').addClass('error');
                    $statusMessage.text('Network error while generating report').show();
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Load and display statistics
     */
    function loadStatistics(forceRefresh = false) {
        $.ajax({
            url: altTextAuditor.ajax_url,
            type: 'POST',
            timeout: 30000,
            data: {
                action: 'alttext_audit_stats',
                nonce: altTextAuditor.audit_nonce,
                force_refresh: forceRefresh ? 'true' : 'false'
            },
            success: function(response) {
                if (response.success) {
                    displayStatistics(response.data);
                } else {
                    $('#audit-stats-cards').html('<p style="color: #d63638;">Failed to load statistics: ' + (response.data?.message || 'Unknown error') + '</p>');
                    showError('Failed to load statistics');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Error loading statistics';
                if (status === 'timeout') {
                    errorMsg = 'Request timed out loading statistics';
                } else if (xhr.responseText && xhr.responseText === '0') {
                    errorMsg = 'AJAX handler not found - please deactivate and reactivate the plugin';
                } else if (xhr.status) {
                    errorMsg = 'Server error (' + xhr.status + ') loading statistics';
                }
                $('#audit-stats-cards').html('<p style="color: #d63638;">' + errorMsg + '</p>');
                showError(errorMsg);
            }
        });
    }

    /**
     * Display statistics in cards
     */
    function displayStatistics(stats) {
        const html = `
            <div class="audit-stat-card">
                <h3>Total Images</h3>
                <span class="audit-stat-number">${stats.total_images}</span>
                <span class="audit-stat-label">Scanned</span>
            </div>

            <div class="audit-stat-card alert">
                <h3>Missing Alt-Text</h3>
                <span class="audit-stat-number">${stats.missing_alt}</span>
                <span class="audit-stat-percentage">${stats.missing_percentage}%</span>
                <span class="audit-stat-label">Need Attention</span>
            </div>

            <div class="audit-stat-card success">
                <h3>Has Alt-Text</h3>
                <span class="audit-stat-number">${stats.has_alt}</span>
                <span class="audit-stat-percentage">${stats.has_percentage}%</span>
                <span class="audit-stat-label">Complete</span>
            </div>

            <div class="audit-stat-card info">
                <h3>Last Scan</h3>
                <span class="audit-stat-number" style="font-size: 20px;">${stats.last_scan_human}</span>
                <span class="audit-stat-label">${stats.last_scan_date || 'Never'}</span>
            </div>
        `;

        $('#audit-stats-cards').html(html);
    }

    /**
     * Start a scan (content or media)
     */
    function startScan(scanType) {
        if (isScanning) {
            alert('A scan is already in progress. Please wait for it to complete.');
            return;
        }

        isScanning = true;
        currentScanType = scanType;
        scanStartTime = Date.now();

        // Disable buttons
        $('#scan-content-btn, #scan-media-btn, #scan-drafts-btn, #clear-cache-btn').prop('disabled', true);

        // Show progress bar
        $('#scan-progress').show();
        updateProgressBar(0);
        updateETA(0, 0, 0);
        updateScanDetails('Initializing scan...');

        // Start scanning from batch 0
        processScanBatch(scanType, 0);
    }

    /**
     * Process a single scan batch recursively
     */
    function processScanBatch(scanType, batch) {
        $.ajax({
            url: altTextAuditor.ajax_url,
            type: 'POST',
            timeout: 60000,
            data: {
                action: 'alttext_audit_scan',
                nonce: altTextAuditor.audit_nonce,
                scan_type: scanType,
                batch: batch
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Update progress bar
                    updateProgressBar(data.percentage);
                    updateProgressText(`${data.message}`);
                    updateETA(data.percentage, data.processed || 0, data.total || 0);

                    // Update scan details with current item info
                    if (data.current_item) {
                        updateScanDetails(data.current_item);
                    }

                    // Continue scanning if not complete
                    if (data.continue) {
                        processScanBatch(scanType, data.current_batch + 1);
                    } else {
                        // Scan complete
                        completeScan(scanType, data);
                    }
                } else {
                    // Check if scan was cancelled
                    if (response.data && response.data.cancelled) {
                        handleScanCancellation(response.data.message);
                    } else {
                        handleScanError(response.data ? response.data.message : 'Unknown error');
                    }
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Network error';
                if (status === 'timeout') {
                    errorMsg = 'Request timed out - batch took too long';
                } else if (xhr.responseText === '0') {
                    errorMsg = 'AJAX handler not found - please deactivate and reactivate the plugin';
                } else if (error) {
                    errorMsg = `Network error: ${error}`;
                }
                handleScanError(errorMsg);
            }
        });
    }

    /**
     * Update progress bar
     */
    function updateProgressBar(percentage) {
        $('.audit-progress-fill').css('width', percentage + '%');
        $('.audit-progress-text .percentage').text(percentage + '%');
    }

    /**
     * Update progress text
     */
    function updateProgressText(text) {
        const currentPercentage = $('.audit-progress-text .percentage').text();
        const currentItems = $('.audit-progress-text .scan-items').text();
        $('.audit-progress-text').html(text + ' <span class="percentage">' + currentPercentage + '</span><span class="scan-items">' + currentItems + '</span>');
    }

    /**
     * Update ETA display
     */
    function updateETA(percentage, processed, total) {
        if (!scanStartTime || percentage === 0) {
            $('.audit-progress-eta .eta-text').text('');
            $('.audit-progress-text .scan-items').text('');
            return;
        }

        // Calculate elapsed time in seconds
        const elapsed = (Date.now() - scanStartTime) / 1000;

        // Calculate estimated total time based on current progress
        const estimatedTotal = (elapsed / percentage) * 100;
        const remaining = estimatedTotal - elapsed;

        // Format ETA
        let etaText = '';
        if (remaining > 0) {
            const minutes = Math.floor(remaining / 60);
            const seconds = Math.floor(remaining % 60);

            if (minutes > 0) {
                etaText = `ETA: ${minutes}m ${seconds}s remaining`;
            } else {
                etaText = `ETA: ${seconds}s remaining`;
            }
        }

        $('.audit-progress-eta .eta-text').text(etaText);

        // Update items count if available
        if (total > 0) {
            $('.audit-progress-text .scan-items').text(` (${processed} of ${total})`);
        }
    }

    /**
     * Update scan details with current item
     */
    function updateScanDetails(item) {
        if (!item || item === lastScannedItem) {
            return;
        }

        lastScannedItem = item;
        const timestamp = new Date().toLocaleTimeString();
        const $content = $('.current-scan-item');

        // Add new line with timestamp
        const newLine = `[${timestamp}] ${item}`;
        $content.text(newLine);
    }

    /**
     * Handle scan completion
     */
    function completeScan(scanType, data) {
        isScanning = false;
        currentScanType = null;
        scanStartTime = null;

        // Update progress to 100%
        updateProgressBar(100);
        updateProgressText('Scan complete!');
        $('.audit-progress-eta .eta-text').text('');

        // Determine scan type label
        const scanLabel = scanType === 'content' ? 'Published content' :
                         scanType === 'drafts' ? 'Draft content' : 'Media library';

        // Show completion message with link to Scans tab
        const scansTabUrl = window.location.href.split('&tab=')[0] + '&tab=scans';
        const message = `${scanLabel} scan completed! Found ${data.processed} images. ` +
                       `<a href="${scansTabUrl}" style="color: #fff; text-decoration: underline; font-weight: 600;">View report in Scans tab →</a>`;

        showSuccess(message);

        // Reload statistics
        setTimeout(function() {
            loadStatistics(true);
            $('#scan-progress').fadeOut();
            resetProgressBar();
            $('#scan-content-btn, #scan-media-btn, #scan-drafts-btn, #clear-cache-btn').prop('disabled', false);
        }, 2000);
    }

    /**
     * Handle scan cancellation
     */
    function handleScanCancellation(message) {
        isScanning = false;
        currentScanType = null;
        scanStartTime = null;

        // Hide progress bar
        $('#scan-progress').fadeOut();
        resetProgressBar();

        // Re-enable buttons
        $('#scan-content-btn, #scan-media-btn, #scan-drafts-btn, #clear-cache-btn').prop('disabled', false);

        // Show info message
        showNotice(message || 'Scan cancelled', 'warning');
    }

    /**
     * Handle scan errors
     */
    function handleScanError(message) {
        isScanning = false;
        currentScanType = null;
        scanStartTime = null;

        // Hide progress bar
        $('#scan-progress').fadeOut();
        resetProgressBar();

        // Re-enable buttons
        $('#scan-content-btn, #scan-media-btn, #scan-drafts-btn, #clear-cache-btn').prop('disabled', false);

        // Show error
        showError('Scan failed: ' + message);
    }

    /**
     * Cancel an in-progress scan
     */
    function cancelScan() {
        if (!isScanning) {
            return;
        }

        if (!confirm('Are you sure you want to cancel this scan? The scan will stop after the current batch completes.')) {
            return;
        }

        // Disable cancel button to prevent multiple clicks
        $('#cancel-scan-btn').prop('disabled', true).text('Cancelling...');

        $.ajax({
            url: altTextAuditor.ajax_url,
            type: 'POST',
            data: {
                action: 'alttext_cancel_scan',
                nonce: altTextAuditor.audit_nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'info');
                } else {
                    showError(response.data.message || 'Failed to cancel scan');
                    $('#cancel-scan-btn').prop('disabled', false).text('Cancel Scan');
                }
            },
            error: function() {
                showError('Network error while cancelling scan');
                $('#cancel-scan-btn').prop('disabled', false).text('Cancel Scan');
            }
        });
    }

    /**
     * Reset progress bar
     */
    function resetProgressBar() {
        setTimeout(function() {
            updateProgressBar(0);
            updateProgressText('Scanning...');
            $('.audit-progress-eta .eta-text').text('');
            $('.audit-progress-text .scan-items').text('');
            $('.current-scan-item').text('Initializing scan...');
            scanStartTime = null;
            lastScannedItem = '';
        }, 500);
    }

    /**
     * Clear statistics cache
     */
    function clearCache() {
        if (confirm('Are you sure you want to clear the statistics cache? This will force a recalculation on next load.')) {
            // Just reload statistics with force refresh
            showSuccess('Cache cleared. Reloading statistics...');
            loadStatistics(true);
        }
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        showNotice(message, 'success');
    }

    /**
     * Show error message
     */
    function showError(message) {
        showNotice(message, 'error');
    }

    /**
     * Show notice
     */
    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible audit-notice ' + type + '"><p>' + message + '</p></div>');

        $('.audit-tab-content').prepend($notice);

        // Make dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Load user attribution data
     */
    function loadUserAttribution() {
        $.ajax({
            url: altTextAuditor.ajax_url,
            type: 'POST',
            data: {
                action: 'alttext_audit_users',
                nonce: altTextAuditor.audit_nonce
            },
            success: function(response) {
                if (response.success) {
                    displayUserAttribution(response.data);
                } else {
                    showError('Failed to load user attribution data');
                }
            },
            error: function() {
                showError('Error loading user attribution data');
            }
        });
    }

    /**
     * Display user attribution table
     */
    function displayUserAttribution(data) {
        if (!data.users || data.users.length === 0) {
            $('#audit-user-results').html(`
                <div class="audit-empty-state">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h3>${'No Missing Alt-Text Found'}</h3>
                    <p>${'Great job! All users have properly added alt-text to their images.'}</p>
                </div>
            `);
            return;
        }

        let html = `
            <table class="wp-list-table widefat fixed striped audit-user-table">
                <thead>
                    <tr>
                        <th class="column-user">User</th>
                        <th class="column-missing-alt">Missing Alt-Text</th>
                        <th class="column-total-images">Total Images</th>
                        <th class="column-percentage">Percentage</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.users.forEach(function(user) {
            let badgeClass = 'low';
            if (user.missing_percentage >= 50) {
                badgeClass = 'high';
            } else if (user.missing_percentage >= 25) {
                badgeClass = 'medium';
            }

            html += `
                <tr>
                    <td class="column-user">
                        <strong class="audit-user-name">${escapeHtml(user.display_name)}</strong>
                        <span class="audit-user-role">${escapeHtml(user.role)}</span>
                    </td>
                    <td class="column-missing-alt">
                        <span class="missing-count">${user.missing_alt}</span>
                    </td>
                    <td class="column-total-images">
                        ${user.total_images}
                    </td>
                    <td class="column-percentage">
                        <span class="percentage-badge ${badgeClass}">${user.missing_percentage}%</span>
                    </td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        // Add summary
        if (data.summary) {
            html = `
                <div class="audit-notice info" style="margin-bottom: 20px;">
                    <p>
                        <strong>Summary:</strong>
                        ${data.summary.users_with_missing} of ${data.summary.total_users} users have images with missing alt-text.
                    </p>
                </div>
            ` + html;
        }

        $('#audit-user-results').html(html);
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Format number with commas
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Bind scans tab interactions
     */
    function bindScansTab() {
        // Select all checkbox
        $('#select-all-scans').on('change', function() {
            $('.scan-checkbox').prop('checked', $(this).prop('checked'));
        });

        // Individual checkbox
        $('.scan-checkbox').on('change', function() {
            const allChecked = $('.scan-checkbox').length === $('.scan-checkbox:checked').length;
            $('#select-all-scans').prop('checked', allChecked);
        });

        // View report button - open modal
        $('.view-report-btn').on('click', function(e) {
            e.preventDefault();
            const reportUrl = $(this).data('report-url');
            openReportModal(reportUrl);
        });

        // Close modal
        $('.report-modal-close, .report-modal-overlay').on('click', function() {
            closeReportModal();
        });

        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#report-modal').is(':visible')) {
                closeReportModal();
            }
        });

        // Bulk actions
        $('#apply-bulk-action').on('click', function() {
            const action = $('#bulk-action-selector-top').val();
            const selectedScans = $('.scan-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (action === '-1') {
                alert('Please select an action.');
                return;
            }

            if (selectedScans.length === 0) {
                alert('Please select at least one scan.');
                return;
            }

            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete ' + selectedScans.length + ' scan(s) and their associated reports? This cannot be undone.')) {
                    return;
                }

                deleteBulkScans(selectedScans);
            }
        });
    }

    /**
     * Open report modal
     */
    function openReportModal(reportUrl) {
        $('#report-iframe').attr('src', reportUrl);
        $('#report-modal').fadeIn(200);
        $('body').css('overflow', 'hidden');
    }

    /**
     * Close report modal
     */
    function closeReportModal() {
        $('#report-modal').fadeOut(200);
        $('#report-iframe').attr('src', '');
        $('body').css('overflow', 'auto');
    }

    /**
     * Delete bulk scans
     */
    function deleteBulkScans(scanIds) {
        $.ajax({
            url: altTextAuditor.ajax_url,
            type: 'POST',
            data: {
                action: 'alttext_delete_scans',
                nonce: altTextAuditor.audit_nonce,
                scan_ids: scanIds
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Deleted ' + response.data.deleted + ' scan(s) successfully.');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showError(response.data.message || 'Failed to delete scans.');
                }
            },
            error: function() {
                showError('Error deleting scans. Please try again.');
            }
        });
    }

    /**
     * Bind data management buttons
     */
    function bindDataManagement() {
        function updateCleanupWarning() {
            var value = $('#auto-cleanup-days').val();
            $('#auto-cleanup-warning').toggleClass('alttext-warning--alert', value === 'never');
        }

        function updateRetentionWarning() {
            var count = parseInt($('#report-retention-count').val(), 10);
            var shouldWarn = !isNaN(count) && count > 50;
            $('#report-retention-warning').toggleClass('alttext-warning--alert', shouldWarn);
        }

        function updateDebugWarning() {
            var enabled = $('#debug-logging-enabled').is(':checked');
            $('#debug-logging-warning').toggleClass('alttext-warning--alert', enabled);
        }

        // Save cleanup setting
        $('#save-cleanup-setting-btn').on('click', function() {
            var $btn = $(this);
            var days = $('#auto-cleanup-days').val();
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: altTextAuditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'alttext_save_cleanup_setting',
                    nonce: altTextAuditor.audit_nonce,
                    days: days
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.data.message);
                    } else {
                        showError(response.data.message || 'Failed to save setting.');
                    }
                },
                error: function() {
                    showError('Error saving setting. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                    updateCleanupWarning();
                }
            });
        });

        $('#auto-cleanup-days').on('change', updateCleanupWarning);
        updateCleanupWarning();

        $('#report-retention-count').on('input change', updateRetentionWarning);
        updateRetentionWarning();

        updateDebugWarning();

        // Save report retention setting
        $('#save-report-retention-btn').on('click', function() {
            var $btn = $(this);
            var count = parseInt($('#report-retention-count').val(), 10);
            var originalText = $btn.text();

            if (isNaN(count) || count < 1 || count > 200) {
                showError('Please enter a number between 1 and 200.');
                return;
            }

            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: altTextAuditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'alttext_save_report_retention',
                    nonce: altTextAuditor.audit_nonce,
                    count: count
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.data.message);
                    } else {
                        showError(response.data.message || 'Failed to save report retention.');
                    }
                },
                error: function() {
                    showError('Error saving report retention. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                    updateRetentionWarning();
                }
            });
        });

        // Toggle debug logging
        $('#debug-logging-enabled').on('change', function() {
            var $checkbox = $(this);
            var enabled = $checkbox.is(':checked');

            $.ajax({
                url: altTextAuditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'alttext_toggle_debug_logging',
                    nonce: altTextAuditor.audit_nonce,
                    enabled: enabled
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.data.message);
                        updateDebugWarning();
                    } else {
                        showError(response.data.message || 'Failed to update debug logging.');
                        $checkbox.prop('checked', !enabled);
                        updateDebugWarning();
                    }
                },
                error: function() {
                    showError('Error updating debug logging. Please try again.');
                    $checkbox.prop('checked', !enabled);
                    updateDebugWarning();
                }
            });
        });

        // Clear debug log
        $('#clear-log-btn').on('click', function() {
            if (!confirm('Clear the debug log now? This cannot be undone.')) {
                return;
            }

            var $btn = $(this);
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: altTextAuditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'alttext_clear_log',
                    nonce: altTextAuditor.audit_nonce
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.data.message);
                        if (response.data.log_size) {
                            $('#alttext-log-size').text(response.data.log_size);
                        }
                    } else {
                        showError(response.data.message || 'Failed to clear log.');
                    }
                },
                error: function() {
                    showError('Error clearing log. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Clear all data
        $('#clear-all-data-btn').on('click', function() {
            if (!confirm('Are you sure you want to delete ALL scan records and reports?\n\nThis will permanently delete:\n- All scan history\n- All HTML reports\n- All scan statistics\n\nThis action CANNOT be undone!')) {
                return;
            }

            // Second confirmation
            if (!confirm('FINAL WARNING: This will delete everything. Are you absolutely sure?')) {
                return;
            }

            var $btn = $(this);
            var originalText = $btn.text();
            var $statusMsg = $('#clear-data-status-message');

            $btn.prop('disabled', true).text('Clearing Data...');
            $statusMsg.hide();

            $.ajax({
                url: altTextAuditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'alttext_clear_all_data',
                    nonce: altTextAuditor.audit_nonce
                },
                success: function(response) {
                    if (response.success) {
                        $statusMsg.removeClass('error').addClass('success');
                        $statusMsg.text(response.data.message).show();
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $statusMsg.removeClass('success').addClass('error');
                        $statusMsg.text(response.data.message || 'Failed to clear data.').show();
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    $statusMsg.removeClass('success').addClass('error');
                    $statusMsg.text('Error clearing data. Please try again.').show();
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Initialize on page load
     */
    initDashboard();

    // Initialize scans tab if on that tab
    if ($('.audit-scans-wrapper').length) {
        bindScansTab();
    }

    // Initialize data management buttons
    bindDataManagement();
});
