/*
 * This file is part of the jwtools2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Jwtools2/ClearFullIndex
 */
define(['jquery',
        'TYPO3/CMS/Backend/Icons',
        'TYPO3/CMS/Backend/Notification'
    ], function($, Icons, Notification) {
    let ClearIndex = {
        me: this
    };

    ClearIndex.clearIndexAction = function() {
        let $activeRows = $('table>tbody>tr');

        if ($activeRows.length > 0) {
            ClearIndex.clearIndexByRows($activeRows, function($row, status, data, response) {
                if (status === 'success') {
                    Icons.getIcon("status-status-permission-granted", Icons.sizes.small).done(function(ok) {
                        $row.find('span.icon').replaceWith(ok);
                    });
                } else if (status === 'error') {
                    alert('Error');
                } else if (status === 'finished') {
                    ClearIndex.displaySuccess('Index of all Sites has been cleared');
                }
            });
        }
    };

    /**
     * Clear index by rows
     *
     * @param {Object} rows
     * @param {function} callback
     */
    ClearIndex.clearIndexByRows = function(rows, callback) {
        if (rows) {
            rows = $(rows).toArray();
            let $row = $(rows.shift());
            let data = {
                'rootPageUid': $row.find(".rootPageUid").data("rootPageUid"),
                configurationNames: [],
                clear: []
            };
            $("input.configurationNames:checked").each(function() {
                data.configurationNames.push($(this).val());
            });
            $("input.clear:checked").each(function() {
                data.clear.push($(this).val());
            });

            $row.find("div.status").show();
            ClearIndex.clearIndexByRow(data, function(status, data, response) {
                callback($row, status, data, response);
                if (status === 'success') {
                    if (rows.length) {
                        ClearIndex.clearIndexByRows(rows, callback);
                    } else {
                        callback($row, 'finished', data, response);
                    }
                }
            });
        }
    };

    /**
     * Clear index by row
     *
     * @param {Object} data
     * @param {function} callback
     */
    ClearIndex.clearIndexByRow = function(data, callback) {
        ClearIndex.executeAjaxRequest(TYPO3.settings.ajaxUrls['jwtools2_clearIndex'], data, function(response, status) {
            if (status === 'success' && response.success) {
                callback('success', data, response);
            } else {
                callback('error', data, response);
            }
        });
    };

    /**
     * Execute AJAX request
     *
     * @param {String} uri
     * @param {Object} data
     * @param {function} callback
     */
    ClearIndex.executeAjaxRequest = function(uri, data, callback) {
        let newData = {
            tx_jwtools2: data
        };
        ClearIndex.currentRequest = $.ajax({
            type: 'POST',
            cache: false,
            url: uri,
            data: newData,
            dataType: 'json',
            success: function(response, status) {
                if (typeof callback === 'function') {
                    callback(response, status);
                }
            },
            error: function(response, status) {
                if (typeof callback === 'function') {
                    callback(response, status);
                }
            }
        });
    };

    /**
     * Display success flash message
     *
     * @param {String} label
     */
    ClearIndex.displaySuccess = function(label) {
        if (typeof label === 'string' && label !== '') {
            Notification.success('Success', label);
        }
    };

    let $button = $("#clearFullIndex");
    $button.on("click", function(event) {
        event.preventDefault();
        ClearIndex.clearIndexAction();
    });
});
