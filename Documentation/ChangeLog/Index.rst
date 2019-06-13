.. include:: ../Includes.txt


.. _changelog:

=========
ChangeLog
=========

**Version 3.1.1**

- Better support for solr from version 3.0 until 9.0

**Version 3.1.0**

- We have fixed two solr patches. Current they are merged for solr version 10, but if you need them for earlier
  versions, you can activate them as XClasses in EM with option 'solrApplyPatches'.
--> https://github.com/TYPO3-Solr/ext-solr/pull/2323/
--> https://github.com/TYPO3-Solr/ext-solr/pull/2324/

**Version 3.0.1**

- Add documentation
- Better description in ext_emconf.php

**Version 3.0.0**

- Add scheduler task to execute SQL-Queries
- Remove TYPO3 7.6 compatibility
- Add compatibility for TYPO3 9.5
