.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

Target group: **Developers, Integrators**

jwtools2 initially does nothing after installation. Each feature has to be activated manually with
Extensionmanager configuration (TYPO3 8.7) or with module Settings since TYPO3 9.5.

Extension configuration
=======================

.. container:: ts-properties

   ================================ =========
   Property                         Default
   ================================ =========
   typo3EnableUidInPageTree_        0
   typo3TransferTypoScriptCurrent_  0
   reduceCategoriesToPageTree_      0
   enableSqlQueryTask_              0
   solrEnable_                      0
   solrSchedulerTaskUid_            0
   solrApplyPatches_                false
   ================================ =========


Property details
================

.. only:: html

   .. contents::
      :local:
      :depth: 1


.. _typo3EnableUidInPageTree:

typo3EnableUidInPageTree
------------------------

Activate this settings to show page UIDs in front of page title in pagetree. Yes, it's the same settings like:

options.pageTree.showPageIdWithTitle = 1


.. _typo3TransferTypoScriptCurrent:

typo3TransferTypoScriptCurrent
------------------------------

This is a really special setting. It transports the value of ``current`` into its Subproperties. Please have a look
to following TypoScript:

.. code-block:: typoscript

  tt_content.list.20.avalex_avalex.stdWrap {
    setContentToCurrent = 1
    cObject = CONTENT
    cObject {
      table = tx_drstmplmodule_domain_model_configuration
      select {
        pidInList = {$drs.root}
      }
      renderObj = TEXT
      renderObj {
        current = 1
        replacement {
          10.search = avalexDefaultChurch
          10.replace.data = FIELD:church
          20.search = avalexDefaultResponsible
          20.replace.data = FIELD:responsible
          30.search = avalexDefaultStreet
          30.replace.dataWrap = {FIELD:street} {FIELD:house_number}
          40.search = avalexDefaultZIP
          40.replace.data = FIELD:zip
          50.search = avalexDefaultCity
          50.replace.data = FIELD:city
        }
      }
    }
  }

After generating the content of plugin Avalex we call ``stdWrap`` and set ``current`` to the output of the plugin with
``setContentToCurrent``. As each pagetree has its own configuration record assigned, we have to retrieve this
configuration record with CONTENT which sends data to ``renderObj`` and now we have a problem: We only have the data
of configuration record available within ``renderObj``. As we have a completely new ContentObjectRenderer-object
here the ``current`` property is empty. So all the replacements in example will be done on an empty string.

If you activate ``typo3TransferTypoScriptCurrent`` we make use of a hook in TYPO3 and transfer the value of
``current`` into child ContentObjects.

This option will only work for cObj types CONTENT and RECORD.


.. _reduceCategoriesToPageTree:

reduceCategoriesToPageTree
--------------------------

Activate this settings to reduce all available sys_category records in Categorytrees to categories which are
created in current pagetree.

We try to get the current Page UID you're editing and slide up until we find a page which is configured as
``isSiteRoot``. Now we get all sys_category records for this pagetree and remove all disallowed categories
from Categorytrees.


.. _enableSqlQueryTask:

enableSqlQueryTask
------------------

Adds a new task to scheduler to execute your individual SQL-Query.


.. _solrEnable:

solrEnable
----------

Activates Solr feature in our jwtools2 Backend module where you can manage your Solr cores and clear individual
index types.


.. _solrSchedulerTaskUid:

solrSchedulerTaskUid
--------------------

If you make use of our Solr Scheduler Task after activating ``solrEnable`` you must copy and paste the UID
of our Scheduler Task into this setting. We need it to show Scheduler Task related information in our
Solr backend module.

.. _solrApplyPatches:

solrApplyPatches
----------------

We have fixed following issues in EXT:solr:
- https://github.com/TYPO3-Solr/ext-solr/pull/2323/
- https://github.com/TYPO3-Solr/ext-solr/pull/2324/

Currently these patches were merged in EXT:solr version 10. If you need these fixes for earlier solr versions you can
activate this checkbox to override them via TYPO3 XClasses.
