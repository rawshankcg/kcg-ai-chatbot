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

      // Header
      $('.preview-header').css('background', headerBg);
      $('.preview-header').css('color', headerText);

      // User Message and related elements
      $('.preview-user-message').css('background', userMsgBg);
      $('.preview-user-message').css('color', userMsgText);
      $('.preview-user-avatar').css('background', userMsgBg);
      $('.preview-user-avatar').css('color', userMsgText);

      // Bot Message and related elements
      $('.preview-bot-message').css('background', botMsgBg);
      $('.preview-bot-message').css('color', botMsgText);
      $('.preview-bot-avatar').css('background', botMsgBg);
      $('.preview-bot-avatar').css('color', botMsgText);

      // Button colors - both chat button and send button
      $('.preview-chat-button').css('background', buttonBg);
      $('.preview-chat-button svg').attr('stroke', buttonText);
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


  });

})(jQuery);