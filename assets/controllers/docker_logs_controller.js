// assets/controllers/docker_logs_controller.js
import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller: auto-starts the backend command when the element connects.
 * Provides auto-scroll functionality to keep the latest logs visible.
 * Usage in Twig: data-controller="docker-logs" 
 *                 data-docker-logs-start-url-value="..."
 *                 data-docker-logs-logs-container-target="..."
 *                 data-docker-logs-messages-target="..."
 */
export default class extends Controller {
  static values = {
    startUrl: String,
    autoScroll: { type: Boolean, default: true }
  };

  static targets = ['logsContainer', 'messages', 'clearButton'];

  connect() {
    console.log('DockerLogsController connected');
    
    // Setup auto-scroll observer
    this.setupAutoScroll();
    
    // Setup clear button functionality
    this.setupClearButton();
    
    // Start the backend command
    if (this.hasStartUrlValue && this.startUrlValue) {
      // Fire-and-forget: logs will stream via Mercure/Turbo Streams
      fetch(this.startUrlValue, { method: 'GET', credentials: 'same-origin' })
        .catch(() => { /* silence errors; display is handled via Mercure */ });
    }
  }

  disconnect() {
    // Clean up observer when controller disconnects
    if (this.mutationObserver) {
      this.mutationObserver.disconnect();
    }
  }

  /**
   * Setup MutationObserver to watch for new log entries and auto-scroll
   */
  setupAutoScroll() {
    if (!this.hasMessagesTarget || !this.hasLogsContainerTarget) {
      console.warn('DockerLogsController: Missing required targets for auto-scroll');
      return;
    }

    // Create MutationObserver to watch for new log entries
    this.mutationObserver = new MutationObserver((mutations) => {
      if (!this.autoScrollValue) return;

      // Check if new nodes were added
      const hasNewNodes = mutations.some(mutation => 
        mutation.type === 'childList' && mutation.addedNodes.length > 0
      );

      if (hasNewNodes) {
        this.scrollToBottom();
      }
    });

    // Start observing the messages container
    this.mutationObserver.observe(this.messagesTarget, {
      childList: true,
      subtree: true
    });

    console.log('Auto-scroll observer setup complete');
  }

  /**
   * Setup clear button functionality
   */
  setupClearButton() {
    if (this.hasClearButtonTarget) {
      this.clearButtonTarget.addEventListener('click', () => {
        this.clearLogs();
      });
    }
  }

  /**
   * Scroll the logs container to the bottom
   */
  scrollToBottom() {
    if (this.hasLogsContainerTarget) {
      const container = this.logsContainerTarget;
      container.scrollTop = container.scrollHeight;
    }
  }

  /**
   * Clear all log messages
   */
  clearLogs() {
    if (this.hasMessagesTarget) {
      this.messagesTarget.innerHTML = '';
    }
  }

  /**
   * Toggle auto-scroll on/off
   */
  toggleAutoScroll() {
    this.autoScrollValue = !this.autoScrollValue;
    console.log(`Auto-scroll ${this.autoScrollValue ? 'enabled' : 'disabled'}`);
  }

  /**
   * Manual scroll to bottom (can be called from template)
   */
  scrollToBottomAction() {
    this.scrollToBottom();
  }
}
