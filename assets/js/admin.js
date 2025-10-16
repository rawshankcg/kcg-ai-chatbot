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

    $('.kcg-process-single').on('click', function (e) {
      e.preventDefault();
      var button = $(this);
      var postId = button.data('post-id');
      var originalText = button.text();
      var statusCell = button.closest('tr').find('td').eq(2);

      button.prop('disabled', true).text(kcgAiChatbotAdmin.strings.processing || 'Processing...');

      $.post(kcgAiChatbotAdmin.ajaxUrl, {
        action: 'kcg_process_single_post',
        post_id: postId,
        nonce: kcgAiChatbotAdmin.processSingleNonce
      }, function (response) {
        if (response.success) {
          button.text('Re-index');
          statusCell.html('<span style="color: #10b981;">✓ Indexed</span>');
        } else {
          alert('Error: ' + response.data);
          button.text(originalText);
        }
      }).fail(function () {
        alert('An unexpected error occurred. Please check the browser console and try again.');
        button.text(originalText);
      }).always(function () {
        button.prop('disabled', false);
      });
    });
  });

})(jQuery);