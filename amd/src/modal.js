// ============================================================================
// FILE: amd/src/modal.js
// ============================================================================
/**
 * Where Are You modal functionality
 *
 * @module     local_whereareyou/modal
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import 'core/modal';
import {get_string} from 'core/str';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';

/**
 * Initialize the modal
 * @param {Object} config Configuration object
 */
export const init = (config) => {
    
    // Wait for page to be ready
    $(document).ready(() => {
        showModal(config);
    });
};

/**
 * Show the modal
 * @param {Object} config Configuration object
 */
const showModal = async (config) => {
    try {
        // Prepare template context with selected values
        const context = {
            ...config,
            department_options: config.department_options.map(option => ({
                ...option,
                selected: option.value === config.current_department
            })),
            position_options: config.position_options.map(option => ({
                ...option,
                selected: option.value === config.current_position
            }))
        };
        
        // Render modal template
        const html = await Templates.render('local_whereareyou/modal', context);
        
        // Add modal to page
        $('body').append(html);
        
        // Show modal
        const $modal = $('#whereareyou-modal');
        $modal.modal('show');
        
        // Bind events
        bindEvents($modal, config);
        
    } catch (error) {
        console.error('Error showing Where Are You modal:', error);
    }
};

/**
 * Bind modal events
 * @param {jQuery} $modal Modal jQuery object
 * @param {Object} config Configuration object
 */
const bindEvents = ($modal, config) => {
    
    // Save button click
    $modal.find('#whereareyou-save').on('click', async function() {
        const $button = $(this);
        const $form = $('#whereareyou-form');
        const department = $form.find('#department-select').val();
        const position = $form.find('#position-select').val();
        
        if (!department || !position) {
            showMessage('Per favore seleziona sia il dipartimento che la posizione.', 'warning');
            return;
        }
        
        // Disable button and show loading
        $button.prop('disabled', true);
        $button.html('<i class="fa fa-spinner fa-spin me-2"></i>Salvataggio...');
        
        try {
            const response = await $.ajax({
                url: M.cfg.wwwroot + '/local/whereareyou/ajax.php',
                method: 'POST',
                data: {
                    action: 'save',
                    department: department,
                    position: position,
                    sesskey: M.cfg.sesskey
                },
                dataType: 'json'
            });
            
            if (response.success) {
                showMessage('Informazioni salvate con successo!', 'success');
                
                // Close modal after 1 second
                setTimeout(() => {
                    $modal.modal('hide');
                    $modal.remove();
                }, 1000);
            } else {
                throw new Error(response.error || 'Errore sconosciuto');
            }
            
        } catch (error) {
            console.error('Save error:', error);
            showMessage('Errore nel salvare le informazioni. Riprova.', 'danger');
        } finally {
            // Re-enable button
            $button.prop('disabled', false);
            $button.html('<i class="fa fa-save me-2"></i>Salva');
        }
    });
    
    // Logout button click
    $modal.find('#whereareyou-logout').on('click', function() {
        window.location.href = M.cfg.wwwroot + '/login/logout.php?sesskey=' + M.cfg.sesskey;
    });
    
    // Prevent modal from closing on backdrop click or escape
    $modal.on('hide.bs.modal', function(e) {
        // Allow programmatic hiding only
        if (!$(this).data('allow-hide')) {
            e.preventDefault();
            return false;
        }
    });
};

/**
 * Show message in modal
 * @param {string} message Message text
 * @param {string} type Alert type (success, warning, danger, info)
 */
const showMessage = (message, type = 'info') => {
    const $messageDiv = $('#whereareyou-message');
    $messageDiv
        .removeClass('alert-success alert-warning alert-danger alert-info')
        .addClass(`alert-${type}`)
        .text(message)
        .removeClass('d-none');
    
    // Auto-hide after 3 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            $messageDiv.addClass('d-none');
        }, 3000);
    }
};

