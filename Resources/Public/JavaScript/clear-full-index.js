import Icons from "@typo3/backend/icons.js";
import Notification from "@typo3/backend/notification.js";

/**
 * Module: @jweiland/jwtools2/clear-full-index
 */
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
          Icons.getIcon("status-status-permission-granted", Icons.sizes.small)
            .then(ok => {
              const iconSpan = row.querySelector('span.icon');
              if (iconSpan) {
                const newIcon = document.createElement('span');
                newIcon.innerHTML = ok;
                iconSpan.parentNode.replaceChild(newIcon, iconSpan);
                this.displaySuccess('Index of all Sites has been cleared');
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
    const formData = new URLSearchParams();
    Object.entries(data).forEach(([key, value]) => {
      formData.append(key, value);
    });
    return fetch(uri, {
      method: 'POST',
      cache: 'no-cache',
      body: formData,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
    }).then(response => response.json());
  }

  displaySuccess(label) {
    if (typeof label === 'string' && label !== '') {
      Notification.success('Success', label);
    }
  }
}

new ClearIndex();
