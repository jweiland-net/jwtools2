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
for each pagetree anymore.

In our jwtools2 backend module you have the possibility to clear individual solr cores by indexer type.

Tasks
-----

Since version 3.0.0 we have a new task to execute your individual SQL-Query.

TYPO3 Settings
--------------

There are some settings for TYPO3 you can activate in extensionmanager instead of writing pageTSconfig. As
an example: One click to show page UID in pagetree.

Commands
--------

We have created a command to execute the update script of extensions via ``class.ext_update.php``. It only starts the
update, but if you have something special or a wizard in this file this command will not help.

Database
--------

If you make use of ConnectionPool::getQueryBuilderForTable() in backend you will also retrieve deleted records. To
prevent that we have created a BackendRestrictionContainer. You can use it on your own or you can use our
method ``JWeiland\Jwtools2\Database::getQueryBuilderForTable()``
