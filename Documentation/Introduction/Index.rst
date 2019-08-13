.. include:: ../Includes.txt

.. _introduction:

============
Introduction
============

.. _what-it-does:

What does it do?
================

Solr-Features
-------------

We deliver a task to update all of your configured Solr indexers. There is no need to create one Solr indexer task
for each PageTree anymore.

In our jwtools2 backend module you have the possibility to clear individual solr cores by indexer type.

Tasks
-----

Since version 3.0.0 we have a new task to execute your individual SQL-Query.

TYPO3 Settings
--------------

There are some settings for TYPO3 you can activate in ExtensionManager instead of writing PageTSConfig. As
an example: One click to show page UID in PageTree.

Commands
--------

We have created a command to execute the update script of extensions via ``class.ext_update.php``. It only starts the
update, but if you have something special or a wizard in this file this command will not help.

Database
--------

If you make use of ConnectionPool::getQueryBuilderForTable() in backend you will also retrieve deleted records. To
prevent that we have created a BackendRestrictionContainer. You can use it on your own or you can use our
method ``JWeiland\Jwtools2\Database::getQueryBuilderForTable()``

ViewHelpers
-----------

Solr.IndexStatusViewHelper
**************************

With this ViewHelper you will get the Progress Status of a Solr Site in percent.

Solr.MemoryStatusViewHelper
***************************

With this ViewHelper you will get the used Memory usage and available system RAM for selected Solr Site.

Solr.NextRunViewHelper
**********************

Calculates next run of Solr Indexer in seconds.

SplitFileRefViewHelper
**********************

With version 3.2.0 we have ported functionality of method GeneralUtility::split_fileref into its own
ViewHelper. This is very useful, if you want your own preview image for videos for example. See an example here:
`Add Preview image to your videos
<https://jweiland.net/typo3/beispiele-anleitungen-faq/allgemeines/vorschaubild-fuer-videos-einbinden.html>`__
