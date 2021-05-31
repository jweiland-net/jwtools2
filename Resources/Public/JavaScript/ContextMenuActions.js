/**
 * Module: TYPO3/CMS/Jwtools2/ContextMenuActions
 *
 * @exports TYPO3/CMS/Jwtools2/ContextMenuActions
 */
define(["TYPO3/CMS/Core/Ajax/AjaxRequest", "TYPO3/CMS/Backend/Hashing/Md5", "jquery"], function (typo3Ajax, hashMd5, jQuery) {
    'use strict';

    // Add surrounding object into jQuery.default
    // Needed in updateFileMetadata()
    jQuery = __importDefault(jQuery);

    /**
     * @exports TYPO3/CMS/ExtensionKey/ContextMenuActions
     */
    let ContextMenuActions = {};

    /**
     * Update/Create sys_file_metadata record
     *
     * @param {string} table
     * @param {int} uid of the sys_file record
     */
    ContextMenuActions.updateFileMetadata = function (table, uid) {
        if (table === "sys_file") {
            let hash = hashMd5.hash(uid).substring(0, 10);
            let url = TYPO3.settings.ajaxUrls.jwtools2_updateFileMetadata;
            let request = {CB: {files: {["_FILE%7C" + hash]: uid}}};

            new typo3Ajax(url).withQueryArguments(request).get().finally(() => {
                // We need refresh of frame to show updated filesize
                top.TYPO3.Backend.ContentContainer.refresh(true);
                top.TYPO3.Notification.success(
                    jQuery.default(this).data("status-title"),
                    jQuery.default(this).data("status-description"),
                    5
                );
            });
        }
    };

    return ContextMenuActions;
});
