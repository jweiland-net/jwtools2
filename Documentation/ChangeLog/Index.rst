.. include:: ../Includes.txt

.. _changelog:

=========
ChangeLog
=========

**Version 5.5.0**

- Add patch to move related translated records (tt_content). See: https://forge.typo3.org/issues/21161

**Version 5.4.1**

- Add option to send reports about updatable extensions as INFO or WARNING

**Version 5.4.0**

- Add report to show updatable TYPO3 extensions

**Version 5.3.2**

- Show ``create/update file metadata`` item just for images and not SVG, PDF and folders.

**Version 5.3.1**

- Update width/height in EXIF before starting TYPO3s extractor service

**Version 5.3.0**

- Add new context menu item to create/update file metadata incl. width/height

**Version 5.2.2**

- EXT:solr in version 11.0.4 has added some more strict types. To be compatible with new version
  we have added these strict types in our XClasses, too. But that way we need EXT:solr at least in version 11.0.4.

**Version 5.2.1**

- Remove "exclude" from sys_language_uid of stored_routes table

**Version 5.2.0**

- Allow execution of multiple SQL queries in task

**Version 5.1.0**

- Add PersistedTableMapper Abstract to store static values without cHash
- Change record-Attribute to data in HtmlFormat VH

**Version 5.0.0**

- Add TYPO3 10.4 compatibility
- Remove TYPO3 8.7 compatibility
- Add HtmlViewHelper with additional record attribute

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
