.. include:: ../Includes.txt

.. _changelog:

=========
ChangeLog
=========

**Version 4.4.0**

- Allow execution of multiple SQL queries in task

**Version 4.3.0**

- Add new option "typo3ExcludeVideoFilesFromFalFilter"

**Version 4.2.2**

- Implement better icons

**Version 4.2.1**

- Implement new Extension Icon
- Security: Do not show complete hashed password in debug output

**Version 4.2.0**

- Add new Symfony Command to convert plain user passwords to salted hashes

**Version 4.1.0**

- New option to show upload fields in top of LinkHandler and ElementBrowser

**Version 4.0.1**

- CleanUp
- Use progress from response in Solr Progressbar

**Version 4.0.0**

With this Update we do not support EXT:solr versions less than 9.0.0 anymore.

- Use of new Site domain model.
- Removed Solr.IndexStatusViewHelper as this Feature is completely realized with JavaScript now.
- Removed module "Clear full Index Queue" as there is a JS-Button for that in overview now.
- Performance: Overview starts much faster now.
- Add Button to retrieve Solr Progress for all Solr Sites

**Version 3.2.0**

- Added SplitFileRef ViewHelper to split file paths into pieces

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
