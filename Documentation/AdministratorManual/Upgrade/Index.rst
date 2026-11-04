..  include:: /Includes.rst.txt


=======
Upgrade
=======

If you upgrade EXT:jwtools2 to a newer version, please read this section
carefully!

Upgrade to Version 8.2.0
========================

..  important::

    This version requires PHP 8.3 and TYPO3 13.4.24 or higher.

New Extension Settings
----------------------

All new features are disabled by default. Please see :ref:`configuration` for
details:

*   :ref:`typo3PreventSavingContentWithInvalidFileMetaData`
*   :ref:`typo3DisableRecordsOnInvalidFileMetaData`
*   :ref:`typo3ReplaceFrontendImagesWithInvalidMetaData`
*   :ref:`typo3PlaceholderImageApiUrl`
*   :ref:`typo3PlaceholderImageStorageFolder`
*   :ref:`enableFileMetaDataOverviewModule`

Required Metadata Columns
-------------------------

If :ref:`typo3RequiredColumnsForFiles` is configured, these columns are now
marked as required in the metadata form of images. Editors can not save the
metadata of an image without filling these columns anymore. Please flush all
caches after the upgrade, as this is part of the cached TCA.

Upgrade to Version 7.0.0
========================

..  important::

    This version is not TYPO3 11 compatible!

Removed Option: `typo3UploadFieldsInTopOfEB`
--------------------------------------------

The `typo3UploadFieldsInTopOfEB` option has been removed from TYPO3 before 6.2
version. This change was necessitated by the introduction of a paginator in File
Abstraction Layer (FAL). As a result, it's no longer possible to adjust the
position of File upload or create fields above the list without Xclass.

Compatibility Fixes and Updates
-------------------------------

*   Compatibility fix for TYPO3 12
*   Testing Framework migrated to TYPO3 Testing Framework

Deprecated and Obsolete Features
--------------------------------

*   Remove support for TYPO3 11 and lower versions
*   Removed `jwtools2:executeExtensionUpdate` command line controller as it is obsolete.
*   Removed deprecated functions and usages
