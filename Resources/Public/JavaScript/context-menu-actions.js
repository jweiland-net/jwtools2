import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Md5 from '@typo3/backend/hashing/md5.js';

/**
 * Module: @jweiland/jwtools2/context-menu-actions
 *
 * @exports @jweiland/jwtools2/context-menu-actions
 */
class ContextMenuActions {

  /**
   * Update/Create sys_file_metadata record
   *
   * @param {string} table
   * @param {int} uid of the sys_file record
   * @param {Object} dataAttributes
   */
  updateFileMetadata(table, uid, dataAttributes) {
    if (table === 'sys_file') {
      const hash = Md5.hash(uid).substring(0, 10);
      const url = TYPO3.settings.ajaxUrls.jwtools2_updateFileMetadata;
      const request = new AjaxRequest(url);
      const queryParameters = { CB: { files: { ["_FILE%7C" + hash]: uid } } };
      request.withQueryArguments(queryParameters).get().finally(() => {
        // We need refresh of frame to show updated filesize
        top.TYPO3.Backend.ContentContainer.refresh(true);
        top.TYPO3.Notification.success(
          dataAttributes.statusTitle,
          dataAttributes.statusDescription,
          5
        );
      });
    }
  }
}

export default new ContextMenuActions();
