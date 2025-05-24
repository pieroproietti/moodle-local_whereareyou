define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    'use strict';

    var Modal = {
        init: function() {
            this.bindEvents();
            this.checkAndShowModal();
        },

        bindEvents: function() {
            $('#save-whereareyou').on('click', this.saveData.bind(this));
            $('#skip-whereareyou').on('click', this.skipModal.bind(this));
            
            // Handle form submission
            $('#whereareyou-form').on('submit', function(e) {
                e.preventDefault();
                this.saveData();
            }.bind(this));
        },

        checkAndShowModal: function() {
            // Check if modal has been dismissed in this session
            if (sessionStorage.getItem('whereareyou_dismissed') === 'true') {
                return;
            }

            // Check if user has already filled the fields
            Ajax.call([{
                methodname: 'local_whereareyou_get_user_data',
                args: {},
                done: function(response) {
                    if (!response.has_data) {
                        // Show modal if fields are empty
                        setTimeout(function() {
                            $('#whereareyou-modal').modal('show');
                        }, 1000); // Small delay to ensure page is loaded
                    }
                },
                fail: function(error) {
                    console.log('Error checking user data:', error);
                }
            }]);
        },

        saveData: function() {
            var department = $('#department-select').val().trim();
            var position = $('#position-select').val().trim();
            
            // Hide previous messages
            this.hideMessages();
            
            if (!department || !position) {
                this.showError('Please fill in all required fields.');
                return;
            }

            // Show loading state
            this.setLoadingState(true);

            Ajax.call([{
                methodname: 'local_whereareyou_save_user_data',
                args: {
                    department: department,
                    position: position
                },
                done: function(response) {
                    if (response.success) {
                        this.showSuccess(response.message);
                        sessionStorage.setItem('whereareyou_dismissed', 'true');
                        setTimeout(function() {
                            $('#whereareyou-modal').modal('hide');
                        }, 2000);
                    } else {
                        this.showError(response.message);
                    }
                }.bind(this),
                fail: function(error) {
                    console.log('Save error:', error);
                    this.showError('An error occurred while saving your information.');
                }.bind(this),
                always: function() {
                    this.setLoadingState(false);
                }.bind(this)
            }]);
        },

        skipModal: function() {
            sessionStorage.setItem('whereareyou_dismissed', 'true');
            $('#whereareyou-modal').modal('hide');
        },

        setLoadingState: function(loading) {
            var $btn = $('#save-whereareyou');
            var $text = $btn.find('.btn-text');
            var $spinner = $btn.find('.spinner-border');
            
            if (loading) {
                $btn.prop('disabled', true);
                $text.text('Saving...');
                $spinner.removeClass('d-none');
            } else {
                $btn.prop('disabled', false);
                $text.text($text.data('original') || 'Save');
                $spinner.addClass('d-none');
            }
        },

        showError: function(message) {
            $('#error-message').text(message).removeClass('d-none');
            $('#success-message').addClass('d-none');
        },

        showSuccess: function(message) {
            $('#success-message').text(message).removeClass('d-none');
            $('#error-message').addClass('d-none');
        },

        hideMessages: function() {
            $('#error-message, #success-message').addClass('d-none');
        }
    };

    return {
        init: Modal.init.bind(Modal)
    };
});
