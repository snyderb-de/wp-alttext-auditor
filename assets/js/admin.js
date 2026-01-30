/**
 * WP Alt Text Updater - Admin JavaScript
 *
 * Handles inline alt-text editing in the WordPress Media Library with
 * debounced auto-save functionality and visual feedback indicators.
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';

    /**
     * Stores debounce timeouts per input field
     * @type {Object.<number, number>}
     */
    let saveTimeouts = {};

    /**
     * Handle alt text input changes with debouncing
     * Waits 1 second after user stops typing before saving
     *
     * @listens input
     */
    $(document).on('input', '.alttext-input', function() {
        const $input = $(this);
        const attachmentId = $input.data('attachment-id');
        const altText = $input.val().trim();
        
        // Clear any existing timeout for this input
        if (saveTimeouts[attachmentId]) {
            clearTimeout(saveTimeouts[attachmentId]);
        }
        
        // Reset input state
        $input.removeClass('saving success error');
        hideAllIndicators($input);
        
        // Set a timeout to save after user stops typing
        saveTimeouts[attachmentId] = setTimeout(function() {
            saveAltText(attachmentId, altText, $input);
        }, 1000); // Wait 1 second after user stops typing
    });
    
    /**
     * Handle Enter key press for immediate save
     * Bypasses debounce delay for instant saving
     *
     * @listens keypress
     * @param {KeyboardEvent} e - Keyboard event object
     */
    $(document).on('keypress', '.alttext-input', function(e) {
        if (e.which === 13) { // Enter key
            const $input = $(this);
            const attachmentId = $input.data('attachment-id');
            const altText = $input.val().trim();
            
            if (saveTimeouts[attachmentId]) {
                clearTimeout(saveTimeouts[attachmentId]);
            }
            saveAltText(attachmentId, altText, $input);
        }
    });
    
    /**
     * Handle blur event to save on focus loss
     * Ensures changes are saved when user clicks away
     *
     * @listens blur
     */
    $(document).on('blur', '.alttext-input', function() {
        const $input = $(this);
        const attachmentId = $input.data('attachment-id');
        const altText = $input.val().trim();
        
        if (saveTimeouts[attachmentId]) {
            clearTimeout(saveTimeouts[attachmentId]);
        }
        saveAltText(attachmentId, altText, $input);
    });
    
    /**
     * Save alt text via AJAX with visual feedback
     *
     * Makes an AJAX POST request to update the attachment's alt-text
     * and provides visual feedback through loading, success, and error states.
     *
     * @param {number} attachmentId - WordPress attachment/media ID
     * @param {string} altText - New alt-text value
     * @param {jQuery} $input - jQuery object of the input element
     */
    function saveAltText(attachmentId, altText, $input) {
        // Don't save if input is empty and was already empty
        const originalValue = $input.attr('data-original-value') || '';
        if (altText === originalValue) {
            return;
        }
        
        // Show loading state
        $input.addClass('saving');
        showSpinner($input);
        
        const data = {
            action: 'update_alt_text',
            attachment_id: attachmentId,
            alt_text: altText,
            nonce: altTextAuditor.nonce
        };
        
        $.ajax({
            url: altTextAuditor.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Show success state
                    $input.removeClass('saving').addClass('success');
                    showSuccessIcon($input);
                    
                    // Update the original value
                    $input.attr('data-original-value', altText);
                    
                    // Hide success indicator after 2 seconds
                    setTimeout(function() {
                        $input.removeClass('success');
                        hideAllIndicators($input);
                    }, 2000);
                } else {
                    handleSaveError($input, response.data ? response.data.message : altTextAuditor.error_text);
                }
            },
            error: function(xhr, status, error) {
                handleSaveError($input, altTextAuditor.error_text);
                console.error('Alt text save error:', error);
            }
        });
    }
    
    /**
     * Handle AJAX save errors with visual feedback
     *
     * Displays error icon and optional tooltip message,
     * automatically removing error state after 3 seconds.
     *
     * @param {jQuery} $input - jQuery object of the input element
     * @param {string} message - Error message to display as tooltip
     */
    function handleSaveError($input, message) {
        $input.removeClass('saving').addClass('error');
        showErrorIcon($input);
        
        // Show error message as tooltip
        if (message) {
            $input.attr('title', message);
        }
        
        // Remove error state after 3 seconds
        setTimeout(function() {
            $input.removeClass('error').removeAttr('title');
            hideAllIndicators($input);
        }, 3000);
    }
    
    /**
     * Show loading spinner indicator
     *
     * @param {jQuery} $input - jQuery object of the input element
     */
    function showSpinner($input) {
        const $indicator = $input.siblings('.alttext-save-indicator');
        hideAllIndicators($input);
        $indicator.find('.spinner').addClass('is-active');
    }

    /**
     * Show success checkmark indicator
     *
     * @param {jQuery} $input - jQuery object of the input element
     */
    function showSuccessIcon($input) {
        const $indicator = $input.siblings('.alttext-save-indicator');
        hideAllIndicators($input);
        $indicator.find('.success-icon').addClass('show');
    }

    /**
     * Show error warning indicator
     *
     * @param {jQuery} $input - jQuery object of the input element
     */
    function showErrorIcon($input) {
        const $indicator = $input.siblings('.alttext-save-indicator');
        hideAllIndicators($input);
        $indicator.find('.error-icon').addClass('show');
    }

    /**
     * Hide all save state indicators
     *
     * @param {jQuery} $input - jQuery object of the input element
     */
    function hideAllIndicators($input) {
        const $indicator = $input.siblings('.alttext-save-indicator');
        $indicator.find('.spinner').removeClass('is-active');
        $indicator.find('.success-icon, .error-icon').removeClass('show');
    }
    
    /**
     * Character counter for alt text inputs
     * Adds visual indicator when approaching character limit (90% of maxlength)
     *
     * @listens input
     */
    $(document).on('input', '.alttext-input', function() {
        const $input = $(this);
        const maxLength = parseInt($input.attr('maxlength')) || 255;
        const currentLength = $input.val().length;
        
        // Add character count indicator if near limit
        if (currentLength > maxLength * 0.9) {
            $input.addClass('near-limit');
        } else {
            $input.removeClass('near-limit');
        }
    });
    
    /**
     * Store original values on first focus
     * Enables change detection to prevent unnecessary saves
     *
     * @listens focus
     */
    $(document).on('focus', '.alttext-input', function() {
        const $input = $(this);
        if (!$input.attr('data-original-value')) {
            $input.attr('data-original-value', $input.val());
        }
    });
    
    /**
     * Global keyboard shortcuts
     * Ctrl/Cmd+S triggers blur event to save currently focused input
     *
     * @listens keydown
     * @param {KeyboardEvent} e - Keyboard event object
     */
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save all modified inputs
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();

            $('.alttext-input:focus').each(function() {
                $(this).trigger('blur');
            });
        }
    });

    /**
     * Live search functionality for Alt Text Manager
     * Filters table rows in real-time as user types
     *
     * @listens input
     */
    let searchTimeout;
    $(document).on('input', '#alttext-search', function() {
        const $searchInput = $(this);
        const searchTerm = $searchInput.val().toLowerCase().trim();

        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Debounce search to avoid excessive filtering
        searchTimeout = setTimeout(function() {
            const $table = $('.wp-list-table tbody');
            const $rows = $table.find('tr');
            let visibleCount = 0;

            if (searchTerm === '') {
                // Show all rows if search is empty
                $rows.show();
                visibleCount = $rows.length;
            } else {
                // Filter rows based on search term
                $rows.each(function() {
                    const $row = $(this);
                    const filename = $row.find('.column-filename strong').text().toLowerCase();
                    const altText = $row.find('.alttext-input').val().toLowerCase();

                    if (filename.includes(searchTerm) || altText.includes(searchTerm)) {
                        $row.show();
                        visibleCount++;
                    } else {
                        $row.hide();
                    }
                });
            }

            // Update stats count
            updateVisibleCount(visibleCount);

            // Show "no results" message if needed
            if (visibleCount === 0 && searchTerm !== '') {
                showNoResultsMessage($table);
            } else {
                removeNoResultsMessage($table);
            }
        }, 300); // Wait 300ms after user stops typing
    });

    /**
     * Update the visible count in the stats bar
     *
     * @param {number} count - Number of visible rows
     */
    function updateVisibleCount(count) {
        const $stats = $('.alttext-stats p');
        const totalMatch = $stats.text().match(/of (\d+) images/);

        if (totalMatch) {
            const total = totalMatch[1];
            $stats.text('Showing ' + count + ' of ' + total + ' images');
        }
    }

    /**
     * Show "no results" message in the table
     *
     * @param {jQuery} $table - jQuery object of the table body
     */
    function showNoResultsMessage($table) {
        if ($table.find('.no-results-row').length === 0) {
            const colCount = $table.closest('table').find('thead th').length;
            const $noResults = $('<tr class="no-results-row"><td colspan="' + colCount + '" style="text-align: center; padding: 40px; color: #666;">' +
                '<span class="dashicons dashicons-search" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 10px;"></span><br>' +
                '<strong>No images found</strong><br>' +
                'Try a different search term or clear the filter.' +
                '</td></tr>');
            $table.append($noResults);
        }
    }

    /**
     * Remove "no results" message from the table
     *
     * @param {jQuery} $table - jQuery object of the table body
     */
    function removeNoResultsMessage($table) {
        $table.find('.no-results-row').remove();
    }

    /**
     * Clear search when reset is clicked
     *
     * @listens click
     */
    $(document).on('click', '.alttext-filter-item a[href*="wp-alttext-manager"]', function(e) {
        const $link = $(this);
        // Only handle the Reset link
        if ($link.text().trim() === 'Reset') {
            $('#alttext-search').val('');
        }
    });
});
