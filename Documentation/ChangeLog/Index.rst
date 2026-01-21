..  include:: /Includes.rst.txt


..  _changelog:

=========
ChangeLog
=========

..  contents::
    :local:

Version 8.1.1
=============

*   [TASK] Simplify access restriction in Backend Module configuration

Version 8.1.0
=============

*   [BUGFIX] Removed ModifyElementInformationHook patch applied from v7.2

Version 8.0.3
=============

*   [BUGFIX] Fixed issue with Initialization method where uid is identifier in request

Version 8.0.2
=============

*   Update testing directory

Version 8.0.1
=============

*   [BUGFIX] Fixed ext_emconf for updating TYPO3 Version

Version 8.0.0
=============

*   [TASK] Compatibility fix for TYPO3 13
*   [TASK] Dropped older version compatibility
*   [TASK] Fixed deprecations and removed obsolete API calls
*   [TASK] Replaced removed hooks with PSR-14 Events

Version 7.1.2
=============

*   [BUGFIX] Correct use statement to QueryGenerator
*   [TASK] Update test environment
*   [DOCS] Switch to new rendering

Version 7.2.0
=============

*   [BUGFIX] Remove ModifyElementInformationHook as it is native in TYPO3 v12

Version 7.1.1
=============

*   [BUGFIX] Fixed argument type casting issue when the scheduler is saved with index queue task.

Version 7.1.0
=============

*   [FEATURE] Throw Exception if cache expression matches to prevend inserting invalid cache entries

Version 7.0.2
=============

*   [BUGFIX] Get Server request from global TYPO3_REQUEST in CF logger

Version 7.0.1
=============

*   [PERFORMANCE] Prevent calling getListOfAllowedCategoryUids multiple times

Version 7.0.0
=============

*   [TASK] Compatibility fix for TYPO3 12
*   [TASK] Testing Framework migrated to TYPO3 Tesing Framework
*   [TASK] Remove support for TYPO3 11 and lower versions
*   [TASK] Removed jwtools2:executeExtensionUpdate command line controller as it is obsolete.
*   [TASK] Removed typo3UploadFieldsInTopOfEB configuration as it is not possible to change upload form without Xclass.
*   [TASK] Removed deprecated functions and usages


Version 6.0.6
=============

*   [BUGFIX] Do not load FileBrowser JS in case of file or folder mode

Version 6.0.5
=============

*   [BUGFIX] Repair upload form on top of EB for TYPO3 11

Version 6.0.4
=============

*   [BUGFIX] Allow switching the folders in FileBrowser. JS error.

Version 6.0.3
=============

*   Use linkThisScript, if there is no returnUrl in URL params

Version 6.0.2
=============

*   Overwrite FileBrowser for file and folder requests only

Version 6.0.1
=============

*   Translate columns in infobox of non-selectable image feature

Version 6.0.0
=============

*   Add TYPO3 11 compatibility
*   Remove TYPO3 9 compatibility
*   Remove EXT:realurl features
*   Add comma to last entry in array elements
*   Remove interface section from TCA
*   Remove LinkHandler XCLASSes. Override file/folder LinkHandlers of TYPO3 directly

Version 5.10.2
==============

*   Add FlashMessage for non selectable files to FileBrowser

Version 5.10.1
==============

*   ModifyElementInformationHook get's lost. Re-add that file

Version 5.10.0
==============

*   Add option to show "edit" button in element information view.

Version 5.9.0
=============

*   Add option to define columns of sys_file/sys_file_metadata as required.

Version 5.8.1
=============

*   Reduce LiveSearch to UID column, if just an INT is given

Version 5.8.0
=============

*   Add feature to enable performance in LiveSearch for admins

Version 5.7.0
=============

*   Add Caching Framework analyzer incl. Logging

Version 5.6.1
=============

*   Better short description for query cache command
*   Use int values for array keys in table formatter for query cache command

Version 5.6.0
=============

*   Add cache query command

Version 5.5.1
=============

*   Add patch to move related translated records (tt_content). See: https://forge.typo3.org/issues/21161
    for TYPO3 9.5

Version 5.5.0
=============

*   Add patch to move related translated records (tt_content). See: https://forge.typo3.org/issues/21161
    for TYPO3 10.4

Version 5.4.1
=============

*   Add option to send reports about updatable extensions as INFO or WARNING

Version 5.4.0
=============

*   Add report to show updatable TYPO3 extensions

Version 5.3.2
=============

*   Show ``create/update file metadata`` item just for images and not SVG, PDF and folders.

Version 5.3.1
=============

*   Update width/height in EXIF before starting TYPO3s extractor service

Version 5.3.0
=============

*   Add new context menu item to create/update file metadata incl. width/height

Version 5.2.2
=============

*   EXT:solr in version 11.0.4 has added some more strict types. To be compatible with new version
    we have added these strict types in our XClasses, too. But that way we need EXT:solr at least in version 11.0.4.

Version 5.2.1
=============

*   Remove "exclude" from sys_language_uid of stored_routes table

Version 5.2.0
=============

*   Allow execution of multiple SQL queries in task

Version 5.1.0
=============

*   Add PersistedTableMapper Abstract to store static values without cHash
*   Change record-Attribute to data in HtmlFormat VH

Version 5.0.0
=============

*   Add TYPO3 10.4 compatibility
*   Remove TYPO3 8.7 compatibility
*   Add HtmlViewHelper with additional record attribute

Version 4.3.0
=============

*   Add new option "typo3ExcludeVideoFilesFromFalFilter"

Version 4.2.2
=============

*   Implement better icons

Version 4.2.1
=============

*   Implement new Extension Icon
*   Security: Do not show complete hashed password in debug output

Version 4.2.0
=============

*   Add new Symfony Command to convert plain user passwords to salted hashes

Version 4.1.0
=============

*   New option to show upload fields in top of LinkHandler and ElementBrowser

Version 4.0.1
=============

*   CleanUp
*   Use progress from response in Solr Progressbar

Version 4.0.0
=============

With this Update we do not support EXT:solr versions less than 9.0.0 anymore.

*   Use of new Site domain model.
*   Removed Solr.IndexStatusViewHelper as this Feature is completely realized with JavaScript now.
*   Removed module "Clear full Index Queue" as there is a JS-Button for that in overview now.
*   Performance: Overview starts much faster now.
*   Add Button to retrieve Solr Progress for all Solr Sites

Version 3.2.0
=============

*   Added SplitFileRef ViewHelper to split file paths into pieces

Version 3.1.1
=============

*   Better support for solr from version 3.0 until 9.0

Version 3.1.0
=============

*   We have fixed two solr patches. Current they are merged for solr version 10, but if you need them for earlier
    versions, you can activate them as XClasses in EM with option 'solrApplyPatches'.
    *  https://github.com/TYPO3-Solr/ext-solr/pull/2323/
    *  https://github.com/TYPO3-Solr/ext-solr/pull/2324/

Version 3.0.1
=============

*   Add documentation
*   Better description in ext_emconf.php

Version 3.0.0
=============

*   Add scheduler task to execute SQL-Queries
*   Remove TYPO3 7.6 compatibility
*   Add compatibility for TYPO3 9.5
