(function ($) {
  'use strict';

  $(document).ready(function () {

    // Initialize color pickers if they exist
    if ($.fn.wpColorPicker && $('.kcg-color-field').length) {
      $('.kcg-color-field').wpColorPicker({
        change: function (event, ui) {
          updateDesignPreview();
        }
      });
    }

    // Function to update design preview
    function updateDesignPreview() {
      // Only run if we're on the design tab
      if ($('.kcg-design-preview').length === 0) {
        return;
      }

      // Get colors from inputs
      var headerBg = $('#kcg_ai_chatbot_header_bg_color').val();
      var headerText = $('#kcg_ai_chatbot_header_text_color').val();
      var userMsgBg = $('#kcg_ai_chatbot_user_msg_bg_color').val();
      var userMsgText = $('#kcg_ai_chatbot_user_msg_text_color').val();
      var botMsgBg = $('#kcg_ai_chatbot_bot_msg_bg_color').val();
      var botMsgText = $('#kcg_ai_chatbot_bot_msg_text_color').val();
      var buttonBg = $('#kcg_ai_chatbot_button_bg_color').val();
      var buttonText = $('#kcg_ai_chatbot_button_text_color').val();

      var assistantAvatar = $('#kcg_ai_chatbot_assistant_avatar').val();
      var buttonIcon = $('#kcg_ai_chatbot_button_icon').val();

      // Header
      $('.preview-header').css('background', headerBg);
      $('.preview-header').css('color', headerText);

      if (assistantAvatar) {
        $('.preview-assistant-avatar').html('<img src="' + assistantAvatar + '" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;" alt="AI Assistant">');
      } else {
        $('.preview-assistant-avatar').html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>');
        $('.preview-assistant-avatar').css('background', 'rgba(255,255,255,0.2)');
      }

      // User Message and related elements
      $('.preview-user-message').css('background', userMsgBg);
      $('.preview-user-message').css('color', userMsgText);
      $('.preview-user-avatar').css('background', userMsgBg);
      $('.preview-user-avatar').css('color', userMsgText);

      // Bot Message and related elements
      $('.preview-bot-message').css('background', botMsgBg);
      $('.preview-bot-message').css('color', botMsgText);
      // Update bot avatar
      if (assistantAvatar) {
        $('.preview-bot-avatar').html('<img src="' + assistantAvatar + '" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;" alt="AI Assistant">');
      } else {
        $('.preview-bot-avatar').html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>');
        $('.preview-bot-avatar').css('background', botMsgBg);
        $('.preview-bot-avatar').css('color', botMsgText);
      }

      // Button colors - both chat button and send button
      $('.preview-chat-button').css('background', buttonBg);
      if (buttonIcon) {
        $('.preview-chat-button').html('<img src="' + buttonIcon + '" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;" alt="Chat">');
      } else {
        $('.preview-chat-button').html('<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="' + buttonText + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>');
      }
      $('.preview-send-button').css('background', buttonBg);
      $('.preview-send-button svg').attr('stroke', buttonText);
    }

    // Run the preview update on load
    if ($('.kcg-design-preview').length) {
      updateDesignPreview();
    }


    $('#test-api-connection').on('click', function () {
      var button = $(this);
      var resultSpan = $('#test-result');

      button.prop('disabled', true);
      resultSpan.html('<span style="color: #666;">' + (kcgAiChatbotAdmin.strings.testing || 'Testing...') + '</span>');

      $.post(kcgAiChatbotAdmin.ajaxUrl, {
        action: 'kcg_test_gemini_connection',
        nonce: kcgAiChatbotAdmin.nonce
      }, function (response) {
        button.prop('disabled', false);
        if (response.success) {
          resultSpan.html('<span style="color: green;">✓ Connection successful!</span>');
        } else {
          resultSpan.html('<span style="color: red;">✗ Connection failed: ' + response.data + '</span>');
        }
      }).fail(function () {
        button.prop('disabled', false);
        resultSpan.html('<span style="color: red;">✗ An unexpected error occurred.</span>');
      });
    });

    // Clear cache handler
    $('#clear-plugin-cache').on('click', function () {
      var button = $(this);
      var resultSpan = $('#clear-cache-result');

      button.prop('disabled', true);
      resultSpan.html('<span style="color: #666;">' + (kcgAiChatbotAdmin.strings.clearingCache || 'Clearing cache...') + '</span>');

      $.post(kcgAiChatbotAdmin.ajaxUrl, {
        action: 'kcg_clear_cache',
        nonce: kcgAiChatbotAdmin.nonce
      }, function (response) {
        button.prop('disabled', false);
        if (response.success) {
          resultSpan.html('<span style="color: green;">✓ ' + response.data + '</span>');
        } else {
          resultSpan.html('<span style="color: red;">✗ ' + response.data + '</span>');
        }

        setTimeout(function () {
          resultSpan.fadeOut();
        }, 3000);
      }).fail(function () {
        button.prop('disabled', false);
        resultSpan.html('<span style="color: red;">✗ An error occurred.</span>');
      });
    });

    // Bulk processing handlers
    $('.kcg-process-all-posts').on('click', function (e) {
      e.preventDefault();

      var button = $(this);
      var postTypes = button.data('post-types');
      var originalText = button.text();
      var progressDiv = $('#kcg-bulk-progress');
      var statusDiv = $('#kcg-bulk-status');

      if (!confirm(kcgAiChatbotAdmin.strings.confirmBulk || 'Are you sure you want to index ALL content? Once done, this cannot be undone.')) {
        return;
      }

      // Disable all bulk buttons
      $('.kcg-process-all-posts, .kcg-process-all-content').prop('disabled', true);
      button.text(kcgAiChatbotAdmin.strings.processing || 'Processing...');

      // Show progress
      progressDiv.show();
      statusDiv.text('Starting bulk processing...');

      $.post(kcgAiChatbotAdmin.ajaxUrl, {
        action: 'kcg_process_all_posts',
        post_types: postTypes,
        nonce: kcgAiChatbotAdmin.processAllNonce
      }, function (response) {
        if (response.success) {
          statusDiv.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
        } else {
          statusDiv.html('<span style="color: red;">✗ Error: ' + response.data + '</span>');
        }
      }).fail(function () {
        statusDiv.html('<span style="color: red;">✗ An unexpected error occurred.</span>');
      }).always(function () {
        // Re-enable buttons
        $('.kcg-process-all-posts, .kcg-process-all-content').prop('disabled', false);
        button.text(originalText);

        // Hide progress after 10 seconds
        setTimeout(function () {
          progressDiv.fadeOut();
        }, 10000);
      });
    });

    // Single post unindex handler
    $(document).on('click', '.kcg-unindex-single:not(.disabled)', function (e) {
      e.preventDefault();
      var button = $(this);
      var postId = button.data('post-id');
      var originalText = button.text();
      var statusCell = button.closest('tr').find('td').eq(2);
      var indexButton = button.siblings('.kcg-process-single');

      if (!confirm(kcgAiChatbotAdmin.strings.confirmUnindex || 'Are you sure you want to remove this content from the knowledge base?')) {
        return;
      }

      button.prop('disabled', true).text(kcgAiChatbotAdmin.strings.processing || 'Processing...');
      indexButton.prop('disabled', true);

      $.post(kcgAiChatbotAdmin.ajaxUrl, {
        action: 'kcg_unindex_single_post',
        post_id: postId,
        nonce: kcgAiChatbotAdmin.unindexSingleNonce
      }, function (response) {
        if (response.success) {
          // Update status cell
          statusCell.html('<span style="color: #6b7280;">- ' + (kcgAiChatbotAdmin.strings.notIndexed || 'Not Indexed') + '</span>');

          // Update index button
          indexButton.text('Index');

          // Disable and style unindex button
          button.addClass('disabled').prop('disabled', true);
          button.css({
            'background': '#f0f0f1',
            'color': '#a7aaad',
            'border-color': '#dcdcde'
          });

          // Show success message
          var successMsg = $('<div style="color: green; font-size: 12px; margin-top: 2px;">✓ Unindexed successfully</div>');
          button.parent().append(successMsg);
          setTimeout(function () {
            successMsg.fadeOut();
          }, 3000);

        } else {
          alert('Error: ' + response.data);
        }
      }).fail(function () {
        alert('An unexpected error occurred. Please try again.');
      }).always(function () {
        button.text(originalText);
        indexButton.prop('disabled', false);
        if (!button.hasClass('disabled')) {
          button.prop('disabled', false);
        }
      });
    });

    // Update the existing index handler to enable unindex button after successful indexing
    $('.kcg-process-single').off('click').on('click', function (e) {
      e.preventDefault();
      var button = $(this);
      var postId = button.data('post-id');
      var originalText = button.text();
      var statusCell = button.closest('tr').find('td').eq(2);
      var unindexButton = button.siblings('.kcg-unindex-single');

      button.prop('disabled', true).text(kcgAiChatbotAdmin.strings.processing || 'Processing...');
      unindexButton.prop('disabled', true);

      $.post(kcgAiChatbotAdmin.ajaxUrl, {
        action: 'kcg_process_single_post',
        post_id: postId,
        nonce: kcgAiChatbotAdmin.processSingleNonce
      }, function (response) {
        if (response.success) {
          button.text('Re-index');
          statusCell.html('<span style="color: #10b981;">✓ Indexed</span>');

          // Enable and style unindex button
          unindexButton.removeClass('disabled').prop('disabled', false);
          unindexButton.css({
            'background': '#dc3232',
            'color': 'white',
            'border-color': '#dc3232'
          });

          // Show success message
          var successMsg = $('<div style="color: green; font-size: 12px; margin-top: 2px;">✓ Indexed successfully</div>');
          button.parent().append(successMsg);
          setTimeout(function () {
            successMsg.fadeOut();
          }, 3000);

        } else {
          alert('Error: ' + response.data);
          button.text(originalText);
        }
      }).fail(function () {
        alert('An unexpected error occurred. Please try again.');
        button.text(originalText);
      }).always(function () {
        button.prop('disabled', false);
        if (!unindexButton.hasClass('disabled')) {
          unindexButton.prop('disabled', false);
        }
      });
    });

    // Convertion Tab 
    let allExpanded = false;
    // Individual session toggle
    $('.kcg-session-header').on('click', function () {
      const sessionId = $(this).data('session-id');
      const content = $(`.kcg-session-content[data-session-id="${sessionId}"]`);
      const icon = $(this).find('.kcg-toggle-icon');

      if (content.is(':visible')) {
        // Collapse
        content.slideUp(300, function () {
          content.css('max-height', '0');
        });
        icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
        icon.css('transform', 'rotate(0deg)');
      } else {
        // Expand
        content.css('max-height', 'none').slideDown(300);
        icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
        icon.css('transform', 'rotate(90deg)');
      }
    });

    // Toggle all sessions
    $('#kcg-toggle-all-sessions').on('click', function () {
      const button = $(this);

      if (!allExpanded) {
        // Expand all
        $('.kcg-session-content').each(function () {
          const content = $(this);
          const sessionId = content.data('session-id');
          const icon = $(`.kcg-session-header[data-session-id="${sessionId}"] .kcg-toggle-icon`);

          if (!content.is(':visible')) {
            content.css('max-height', 'none').slideDown(300);
            icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
            icon.css('transform', 'rotate(90deg)');
          }
        });
        button.text(kcgAiChatbotAdmin.strings.collapseAll || 'Collapse All Sessions');
        allExpanded = true;
      } else {
        // Collapse all
        $('.kcg-session-content').each(function () {
          const content = $(this);
          const sessionId = content.data('session-id');
          const icon = $(`.kcg-session-header[data-session-id="${sessionId}"] .kcg-toggle-icon`);

          if (content.is(':visible')) {
            content.slideUp(300, function () {
              content.css('max-height', '0');
            });
            icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
            icon.css('transform', 'rotate(0deg)');
          }
        });
        button.text(kcgAiChatbotAdmin.strings.expandAll || 'Expand All Sessions');
        allExpanded = false;
      }
    });

    // Delete session functionality
    $('.kcg-delete-session').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const sessionId = $(this).data('session-id');
      const sessionContainer = $(this).closest('.kcg-session-container');

      console.log('Deleting session ID:', sessionId);

      if (!confirm(kcgAiChatbotAdmin.strings.confirmDeleteSession || 'Are you sure you want to delete this entire session? This action cannot be undone.')) {
        return;
      }

      // Add loading state
      $(this).prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span>');

      $.post(kcgAiChatbotAdmin.ajaxUrl, {
        action: 'kcg_delete_session',
        session_id: sessionId,
        nonce: kcgAiChatbotAdmin.deleteSessionNonce
      }, function (response) {
        if (response.success) {
          sessionContainer.fadeOut(300, function () {
            $(this).remove();

            // Check if no sessions left
            if ($('.kcg-session-container').length === 0) {
              location.reload();
            }
          });
        } else {
          alert('Error: ' + response.data);
        }
      }).fail(function () {
        alert('An unexpected error occurred.');
      });
    });


    // Check if we're on a page that needs media uploader
    if ($('.kcg-upload-image-btn').length > 0) {

      // Make sure wp.media is available
      if (typeof wp !== 'undefined' && wp.media) {

        $('.kcg-upload-image-btn').on('click', function (e) {
          e.preventDefault();

          var button = $(this);
          var fieldId = button.data('field');
          var field = $('#' + fieldId);
          var preview = button.closest('.kcg-image-upload-container').find('.kcg-image-preview');
          var removeBtn = button.siblings('.kcg-remove-image-btn');

          // Create a new media uploader instance
          var mediaUploader = wp.media({
            title: 'Choose Image',
            button: {
              text: 'Choose Image'
            },
            multiple: false,
            library: {
              type: 'image'
            }
          });

          // When an image is selected, run a callback
          mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();

            // Set the field value
            field.val(attachment.url);

            // Update preview
            var maxSize = fieldId.includes('button') ? '60px' : '64px';
            preview.html('<img src="' + attachment.url + '" style="max-width: ' + maxSize + '; max-height: ' + maxSize + '; border-radius: 50%; border: 2px solid #ddd; object-fit: cover;">');

            // Show remove button
            removeBtn.show();

            // Update design preview if function exists
            if (typeof updateDesignPreview === 'function') {
              updateDesignPreview();
            }
          });

          // Open the uploader dialog
          mediaUploader.open();
        });

      } else {
        // Fallback if wp.media is not available
        $('.kcg-upload-image-btn').on('click', function (e) {
          e.preventDefault();
          alert('WordPress media library is not available. Please make sure you are on the correct admin page.');
        });
      }

      // Remove image functionality
      $('.kcg-remove-image-btn').on('click', function (e) {
        e.preventDefault();

        var button = $(this);
        var fieldId = button.data('field');
        var field = $('#' + fieldId);
        var preview = button.closest('.kcg-image-upload-container').find('.kcg-image-preview');

        // Clear field and preview
        field.val('');
        preview.empty();
        button.hide();

        // Update design preview if function exists
        if (typeof updateDesignPreview === 'function') {
          updateDesignPreview();
        }
      });
    }


  });

})(jQuery);