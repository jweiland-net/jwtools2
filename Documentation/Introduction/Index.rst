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

jwtools2:executeExtensionUpdate
###############################

With this command you can execute the update script of extensions via ``class.ext_update.php``. It only starts the
update, but if you have something special or a wizard in this file this command will not help.

jwtools2:convertpasswords
#########################

Use this command to update plain passwords of be_users or fe_users to hashed password using
the currently configured hashing method.
Be careful: This command does not know, if password in DB is a plain password or a md5 password. The Command loops
over all configured Hash Methods of TYPO3. If no Hash Method was found for current password in database, the
password will be updated.

Database
--------

If you make use of ConnectionPool::getQueryBuilderForTable() in backend you will also retrieve deleted records. To
prevent that we have created a BackendRestrictionContainer. You can use it on your own or you can use our
method ``JWeiland\Jwtools2\Database::getQueryBuilderForTable()``

ViewHelpers
-----------

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
