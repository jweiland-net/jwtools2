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
import { getIcon } from 'TYPO3/CMS/Backend/Icons';
import { success as showSuccessNotification } from 'TYPO3/CMS/Backend/Notification';

class ClearIndex {
  constructor() {
    this.clearIndexAction = this.clearIndexAction.bind(this);
    this.clearIndexByRows = this.clearIndexByRows.bind(this);
    this.clearIndexByRow = this.clearIndexByRow.bind(this);
    this.executeAjaxRequest = this.executeAjaxRequest.bind(this);
    this.displaySuccess = this.displaySuccess.bind(this);
    this.$button = document.getElementById("clearFullIndex");
    this.$button.addEventListener("click", this.clearIndexAction);
  }

  clearIndexAction() {
    const activeRows = document.querySelectorAll('table>tbody>tr');

    if (activeRows.length > 0) {
      this.clearIndexByRows(activeRows);
    }
  }

  clearIndexByRows(rows) {
    if (rows) {
      rows.forEach(row => {
        const rootPageUid = row.querySelector(".rootPageUid").dataset.rootPageUid;
        const configurationNames = Array.from(document.querySelectorAll("input.configurationNames:checked")).map(input => input.value);
        const clear = Array.from(document.querySelectorAll("input.clear:checked")).map(input => input.value);

        row.querySelector("div.status").style.display = 'block';
        this.clearIndexByRow({ rootPageUid, configurationNames, clear }, row);
      });
    }
  }

  clearIndexByRow(data, row) {
    this.executeAjaxRequest(TYPO3.settings.ajaxUrls['jwtools2_clearIndex'], data)
      .then(response => {
        if (response.success) {
          getIcon("status-status-permission-granted", "small")
            .then(ok => {
              const iconSpan = row.querySelector('span.icon');
              if (iconSpan) {
                iconSpan.parentNode.replaceChild(ok, iconSpan);
              }
            })
            .catch(error => {
              console.error('Error getting icon:', error);
            });
        } else {
          alert('Error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
  }

  executeAjaxRequest(uri, data) {
    const newData = { tx_jwtools2: data };
    return fetch(uri, {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify(newData),
      headers: {
        'Content-Type': 'application/json'
      }
    })
      .then(response => response.json());
  }

  displaySuccess(label) {
    if (typeof label === 'string' && label !== '') {
      showSuccessNotification('Success', label);
    }
  }
}

new ClearIndex();
