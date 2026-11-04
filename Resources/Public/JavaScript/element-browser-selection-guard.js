import Notification from '@typo3/backend/notification.js';

/**
 * Module: @jweiland/jwtools2/element-browser-selection-guard
 *
 * TYPO3 core hides the checkbox and the "select" button of files which are marked as not selectable
 * (see IsFileSelectableEvent), but a click on the file name still inserts the file. This module
 * intercepts such clicks in the capture phase, before the core file list action handler receives them.
 *
 * @exports @jweiland/jwtools2/element-browser-selection-guard
 */
class ElementBrowserSelectionGuard {
  static blockedActions = ['primary', 'select'];

  constructor() {
    document.addEventListener('click', (event) => this.handleClick(event), true);
  }

  /**
   * @param {MouseEvent} event
   */
  handleClick(event) {
    if (!(event.target instanceof Element)) {
      return;
    }

    const trigger = event.target.closest('[data-filelist-action]');
    if (trigger === null || !ElementBrowserSelectionGuard.blockedActions.includes(trigger.dataset.filelistAction)) {
      return;
    }

    const element = trigger.closest('[data-filelist-element]');
    if (
      element === null
      || element.dataset.filelistType !== 'file'
      || element.dataset.filelistSelectable !== 'false'
    ) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    Notification.warning(
      this.translate('fileBrowser.notification.notSelectable.title'),
      this.translate('fileBrowser.notification.notSelectable.message').replace('%s', element.dataset.filelistName ?? ''),
    );
  }

  /**
   * @param {string} key
   * @returns {string}
   */
  translate(key) {
    return window.TYPO3?.lang?.[key] ?? key;
  }
}

export default new ElementBrowserSelectionGuard();
