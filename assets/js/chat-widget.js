// =============================================================================
// FILE: assets/js/chat-widget.js
// =============================================================================

(function ($) {
  'use strict';

  class KCGChatbot {
    constructor() {
      this.container = $('#kcg-ai-chatbot-container');
      this.button = $('#kcg-chatbot-button');
      this.window = $('#kcg-chatbot-window');
      this.closeBtn = $('#kcg-chatbot-close');
      this.form = $('#kcg-chatbot-form');
      this.input = $('#kcg-chatbot-input');
      this.sendBtn = $('#kcg-chatbot-send');
      this.messagesContainer = $('#kcg-chatbot-messages');
      this.typingIndicator = $('#kcg-chatbot-typing');
      this.sessionIdInput = $('#kcg-chatbot-session-id');

      this.sessionId = this.getSessionId();
      this.isOpen = false;
      this.isTyping = false;

      this.init();
    }

    init() {
      // Event listeners
      this.button.on('click', () => this.toggleChat());
      this.closeBtn.on('click', () => this.closeChat());
      this.form.on('submit', (e) => this.handleSubmit(e));
      this.input.on('input', () => this.autoResizeTextarea());
      this.input.on('keydown', (e) => this.handleKeyDown(e));

      // Show container
      this.container.removeClass('kcg-chatbot-hidden');
    }

    toggleChat() {
      if (this.isOpen) {
        this.closeChat();
      } else {
        this.openChat();
      }
    }

    openChat() {
      this.window.fadeIn(300);
      this.button.addClass('kcg-chatbot-button-active');
      this.isOpen = true;
      this.input.focus();
      this.scrollToBottom();
    }

    closeChat() {
      this.window.fadeOut(300);
      this.button.removeClass('kcg-chatbot-button-active');
      this.isOpen = false;
    }

    handleSubmit(e) {
      e.preventDefault();

      const message = this.input.val().trim();

      if (!message || this.isTyping) {
        return;
      }

      // Clear input
      this.input.val('').css('height', 'auto');

      // Add user message to chat
      this.addUserMessage(message);

      // Send to API
      this.sendMessage(message);
    }

    handleKeyDown(e) {
      // Send message on Enter (without Shift)
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.form.submit();
      }
    }

    autoResizeTextarea() {
      const textarea = this.input[0];
      textarea.style.height = 'auto';
      textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    addUserMessage(message) {
      const template = $('#kcg-user-message-template').html();
      const $message = $(template);

      $message.find('.kcg-message-bubble').text(message);
      $message.find('.kcg-message-time').text(this.getCurrentTime());

      this.messagesContainer.append($message);
      this.scrollToBottom();
    }

    addBotMessage(message) {
      const template = $('#kcg-bot-message-template').html();
      const $message = $(template);

      $message.find('.kcg-message-bubble').html(this.formatMessage(message));
      $message.find('.kcg-message-time').text(this.getCurrentTime());

      this.messagesContainer.append($message);
      this.scrollToBottom();
    }

    addErrorMessage(message) {
      const template = $('#kcg-error-message-template').html();
      const $message = $(template);

      $message.find('.kcg-message-bubble').text(message);

      this.messagesContainer.append($message);
      this.scrollToBottom();
    }

    showTypingIndicator() {
      this.typingIndicator.fadeIn(200);
      this.isTyping = true;
      this.scrollToBottom();
    }

    hideTypingIndicator() {
      this.typingIndicator.fadeOut(200);
      this.isTyping = false;
    }

    sendMessage(message) {
      this.showTypingIndicator();

      const data = {
        message: message,
        session_id: this.sessionId
      };

      $.ajax({
        url: kcgAiChatbot.restUrl + 'chat',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', kcgAiChatbot.nonce);
        },
        success: (response) => {
          this.hideTypingIndicator();

          if (response.success && response.response) {
            this.addBotMessage(response.response);

            // Update session ID if provided
            if (response.session_id) {
              this.sessionId = response.session_id;
              this.saveSessionId(response.session_id);
            }
          } else {
            this.addErrorMessage('Sorry, I could not process your request.');
          }
        },
        error: (xhr, status, error) => {
          this.hideTypingIndicator();
          console.error('Chat error:', error);
          this.addErrorMessage('Sorry, something went wrong. Please try again.');
        }
      });
    }

    formatMessage(message) {
      // Basic formatting: convert line breaks to <br>
      message = message.replace(/\n/g, '<br>');

      // Convert URLs to links
      const urlPattern = /(https?:\/\/[^\s]+)/g;
      message = message.replace(urlPattern, '<a href="$1" target="_blank" rel="noopener">$1</a>');

      return message;
    }

    scrollToBottom() {
      setTimeout(() => {
        this.messagesContainer.animate({
          scrollTop: this.messagesContainer[0].scrollHeight
        }, 300);
      }, 100);
    }

    getSessionId() {
      // Check input field first
      let sessionId = this.sessionIdInput.val();

      if (!sessionId) {
        // Check localStorage
        sessionId = localStorage.getItem('kcg_chatbot_session_id');
      }

      if (!sessionId) {
        // Generate new session ID
        sessionId = this.generateSessionId();
        this.saveSessionId(sessionId);
      }

      this.sessionIdInput.val(sessionId);
      return sessionId;
    }

    saveSessionId(sessionId) {
      this.sessionIdInput.val(sessionId);
      localStorage.setItem('kcg_chatbot_session_id', sessionId);
    }

    generateSessionId() {
      return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    getCurrentTime() {
      const now = new Date();
      let hours = now.getHours();
      const minutes = now.getMinutes();
      const ampm = hours >= 12 ? 'PM' : 'AM';

      hours = hours % 12;
      hours = hours ? hours : 12;
      const minutesStr = minutes < 10 ? '0' + minutes : minutes;

      return hours + ':' + minutesStr + ' ' + ampm;
    }
  }

  // Initialize chatbot when document is ready
  $(document).ready(function () {
    if (typeof kcgAiChatbot !== 'undefined') {
      window.kcgChatbotInstance = new KCGChatbot();
    } else {
      console.error('KCG AI Chatbot: Configuration not found');
    }
  });

})(jQuery);