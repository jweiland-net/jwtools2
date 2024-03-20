import Notification from "@typo3/backend/notification.js";

/**
 * Module: @jweiland/jwtools2/solr-backend-module
 */
class SolrIndex {
  constructor() {
    this.showSolrProgressButton = document.getElementById('showSolrProgress');
    this.createSolrIndexQueueButton = document.getElementById('createSolrIndexQueue');
    this.activeRows = document.querySelectorAll('#solrSites tbody tr');
    this.init();
  }

  init() {
    if (this.showSolrProgressButton) {
      this.showSolrProgressButton.addEventListener('click', (event) => {
        event.preventDefault();
        this.showSolrProgress();
      });
    }

    if (this.createSolrIndexQueueButton) {
      this.createSolrIndexQueueButton.addEventListener('click', (event) => {
        event.preventDefault();
        this.createSolrIndexQueue();
      });
    }
  }

  showSolrProgress() {
    if (this.activeRows.length > 0) {
      this.processRows('jwtools2_getSolrProgress', (row, status, data, response) => {
        console.log(status);
        if (status === 'success') {
          console.log(response);
          row.querySelector('.status').innerHTML = `
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" aria-valuenow="${response.progress}" aria-valuemin="0" aria-valuemax="100" style="width: ${response.progress}%;"><span>${response.progress}% Complete</span></div>
                        </div>`;
        } else if (status === 'error') {
          console.log('Error');
          this.displayError();
        } else if (status === 'finished') {
          console.log(response);
          this.displaySuccess('Solr Status all all Sites has been updated');
        }
      });
    }
  }

  createSolrIndexQueue() {
    if (this.activeRows.length > 0) {
      this.processRows('jwtools2_createSolrIndexQueue', (row, status, data, response) => {
        if (status === 'success') {
          row.querySelector('.status').textContent = 'Solr Index Queue created';
        } else if (status === 'error') {
          alert('Error');
        } else if (status === 'finished') {
          this.displaySuccess('Solr Index Queue of all Solr Site has been created');
        }
      });
    }
  }

  processRows(uri, callback) {
    if (this.activeRows.length > 0) {
      const row = this.activeRows[0];
      const data = { 'rootPageUid': row.querySelector('.rootPageUid').dataset.rootPageUid };
      this.processRow(data, uri, (status, data, response) => {
        callback(row, status, data, response);
        if (status === 'success') {
          if (this.activeRows.length > 1) {
            this.activeRows = Array.from(this.activeRows).slice(1);
            this.processRows(uri, callback);
          } else {
            callback(row, 'finished', data, response);
          }
        }
      });
    }
  }

  processRow(data, uri, callback) {
    this.executeAjaxRequest(TYPO3.settings.ajaxUrls[uri], data, (response, status) => {
      if (status === 'success' && response.success) {
        callback('success', data, response);
      } else {
        callback('error', data, response);
      }
    });
  }

  executeAjaxRequest(uri, data, callback) {
    const formData = new URLSearchParams();
    Object.entries(data).forEach(([key, value]) => {
      formData.append(key, value);
    });
    fetch(uri, {
      method: 'POST',
      cache: 'no-cache',
      body: formData,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
    }).then(response => response.json())
      .then(response => callback(response, 'success'))
      .catch(error => callback(error, 'error'));
  }

  displaySuccess(label) {
    if (typeof label === 'string' && label !== '') {
      Notification.success('Success', label);
    }
  }

  displayError () {
    console.log('Error!')
  }
}

const solrIndex = new SolrIndex();
