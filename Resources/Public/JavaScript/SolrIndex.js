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
 * Module: TYPO3/CMS/Jwtools2/SolrIndex
 */
define(['jquery',
        'TYPO3/CMS/Backend/Icons',
        'TYPO3/CMS/Backend/Notification'
    ], function($, Icons, Notification) {
    let SolrIndex = {
        me: this
    };

    SolrIndex.showSolrProgress = function() {
        let $activeRows = $('#solrSites tbody tr');

        if ($activeRows.length > 0) {
            SolrIndex.processRows(
                $activeRows,
                'jwtools2_getSolrProgress',
                function($row, status, data, response) {
                    if (status === 'success') {
                        $row.find(".status").html("" +
                            "<div class=\"progress\">" +
                            "<div class=\"progress-bar\" role=\"progressbar\" aria-valuenow=\"" + response.progress + "\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"width: " + response.progress + "%;\">" +
                            "<span>" + response.progress + "% Complete</span>" +
                            "</div>" +
                            "</div>"
                        );
                    } else if (status === 'error') {
                        alert('Error');
                    } else if (status === 'finished') {
                        SolrIndex.displaySuccess('Solr Status all all Sites has been updated');
                    }
                });
        }
    };

    SolrIndex.createSolrIndexQueue = function() {
        let $activeRows = $('#solrSites tbody tr');

        if ($activeRows.length > 0) {
            SolrIndex.processRows(
                $activeRows,
                'jwtools2_createSolrIndexQueue',
                function($row, status, data, response) {
                    if (status === 'success') {
                        $row.find(".status").html("Solr Index Queue created");
                    } else if (status === 'error') {
                        alert('Error');
                    } else if (status === 'finished') {
                        SolrIndex.displaySuccess('Solr Index Queue of all Solr Site has been created');
                    }
                });
        }
    };

    /**
     * Show solr progress for rows
     *
     * @param {Object} rows
     * @param {String} uri
     * @param {function} callback
     */
    SolrIndex.processRows = function(rows, uri, callback) {
        if (rows) {
            rows = $(rows).toArray();
            let $row = $(rows.shift());
            let data = {
                'rootPageUid': $row.find(".rootPageUid").data("rootPageUid")
            };

            SolrIndex.processRow(data, uri, function(status, data, response) {
                callback($row, status, data, response);
                if (status === 'success') {
                    if (rows.length) {
                        SolrIndex.processRows(rows, uri, callback);
                    } else {
                        callback($row, 'finished', data, response);
                    }
                }
            });
        }
    };

    /**
     * Show solr status for row
     *
     * @param {Object} data
     * @param {String} uri
     * @param {function} callback
     */
    SolrIndex.processRow = function(data, uri, callback) {
        SolrIndex.executeAjaxRequest(
            TYPO3.settings.ajaxUrls[uri],
            data,
            function(response, status) {
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
    SolrIndex.executeAjaxRequest = function(uri, data, callback) {
        let newData = {
            tx_jwtools2: data
        };
        SolrIndex.currentRequest = $.ajax({
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
    SolrIndex.displaySuccess = function(label) {
        if (typeof label === 'string' && label !== '') {
            Notification.success('Success', label);
        }
    };

    let $showSolrProgressButton = $("#showSolrProgress");
    $showSolrProgressButton.on("click", function(event) {
        event.preventDefault();
        SolrIndex.showSolrProgress();
    });
    let $createSolrIndexQueueButton = $("#createSolrIndexQueue");
    $createSolrIndexQueueButton.on("click", function(event) {
        event.preventDefault();
        SolrIndex.createSolrIndexQueue();
    });
});
