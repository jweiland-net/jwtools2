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

   =============================================== =========
   Property                                         Default
   =============================================== =========
   typo3EnableUidInPageTree_                        0
   typo3TransferTypoScriptCurrent_                  0
   typo3UploadFieldsInTopOfEB_                      0
   typo3ExcludeVideoFilesFromFalFilter_             0
   typo3ApplyFixForMoveTranslatedContentElements_   0
   reduceCategoriesToPageTree_                      0
   enableSqlQueryTask_                              0
   enableContextMenuToUpdateFileMetadata_           0
   enableReportProvider_                            0
   sendUpdatableExtensionsWithSeverity_             info
   solrEnable_                                      0
   solrSchedulerTaskUid_                            0
   solrApplyPatches_                                false
   =============================================== =========


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


.. _typo3UploadFieldsInTopOfEB:

typo3UploadFieldsInTopOfEB
--------------------------

With TYPO3 8.7.0 following UserTSconfig was removed:

options.uploadFieldsInTopOfEB = 1

https://review.typo3.org/c/Packages/TYPO3.CMS/+/52170

After activating this Option we XClasses 3 files to show
Upload Fields in top of ElementBrowser and LinkHandler again.


.. _typo3ExcludeVideoFilesFromFalFilter:

typo3ExcludeVideoFilesFromFalFilter
-----------------------------------

Hidden files are normally hidden in filelist module of TYPO3. Ok, you can activate hidden files in filelist
in your User settings, but may be this Checkbox was hidden by an Integrator or Administrator. Or maybe showing all
these system files like .htaccess, .htpasswd, .DS_Store is a little bit too much.
If your editor creates a new external video TYPO3 stored this video information in a .youtube and/or .vimeo file.
If a title could not be created the files name is still ".youtube". On Mac and Linux Operating Systems files starting
with a dot are handled as hidden files. So it is not possible for an editor to rename, edit or show this file.
Activating this option will still not show hidden files in general, except files with .youtube and .vimeo
file ending.


.. _typo3ApplyFixForMoveTranslatedContentElements:

typo3ApplyFixForMoveTranslatedContentElements
-----------------------------------

If you move a content element (tt_content) from one col to another (backend_layout) the related translated
records will not be moved to new col. This is a problem for over 11 years in TYPO3.
See: https://forge.typo3.org/issues/21161

Activate this option to apply a patch (hook) to solve this problem. We add the missing DB queries to move
the related translated records to new colPos, too.

BUT: Currently I haven't found a solution to hook into JavaScript of TYPO3 to move the translated records directly.
So after a move of tt_content records you have to reload the right frame on your own. If you have a cool idea how
to solve that the nice way feel free to create a PullRequest to jwtools2 ;-)


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


.. _enableContextMenuToUpdateFileMetadata:

enableContextMenuToUpdateFileMetadata
-------------------------------------

Adds a new entry ``Create/Update file metadata`` into context menu of filelist module to create a missing file
metadata record or to update the existing metadata record (sys_file_metadata).

This entry will read original width/height from file and uses them to create a NEW file (imagemagick) with same
dimension, 100% quality and colorspace RGB to update width/height also in EXIF metadata. That's needed for the
registered file extractors like OnlineHelper and EXT:tika which may read width/heigth from EXIF instead, which could
be wrong in some cases. Because of different image tools (Photoshop, Paint, Gimp) the original file may result in a
different image size after process with imagemagick/graphicsmagick.


.. _enableReportProvider:

enableReportProvider
--------------------

If EXT:reports is installed and activated this option will add additional information to reports module. These
information will also be available in status report mail, if configured in scheduler.

Currently following information will be shown:

* List of all (not only security related) updatable extensions incl. version number.
* ...


.. _sendUpdatableExtensionsWithSeverity:

sendUpdatableExtensionsWithSeverity
-----------------------------------

**Default**: info

Only valid, if option ``enableReportProvider`` was activated and you
make use of the ``System Status Update (reports)`` task.

The information about updatable extensions has a severity of type INFO by default. It does not make sense for us to
categorize bugfix extensions as WARNING. But why should YOU decide about that?

There is a checkbox called ``Always send notification mail (not only on errors or warnings)`` in
``System Status Update (reports)`` task which is deactivated by default. As we categorize updatable extensions
as INFO you will not be notified about them in status mail. But if you activate the checkbox in task you will
be notified about various system status and of cause updatable extensions with each scheduler run. Yes, this can be
a lot of mails and a lot of content to search for the right section. Maybe a frustrating job.

Setting ``sendUpdatableExtensionsWithSeverity`` to ``warning`` will set severity of updatable extensions to
``WARNING``. Now, you can leave the checkbox in task deactivated and you will only get mails, if there are
warnings in your TYPO3 system and/or updatable extensions.


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
