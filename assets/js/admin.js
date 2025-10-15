(function ($) {
  'use strict';

  $(document).ready(function () {

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