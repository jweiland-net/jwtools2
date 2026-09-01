..  include:: /Includes.rst.txt


=======
Upgrade
=======

If you upgrade EXT:jwtools2 to a newer version, please read this section
carefully!

Upgrade to Version 8.1.2
========================

The IsFileSelectableEventListener class has been removed completely. This is a
breaking change. However, since the previous implementation required patching the
TYPO3 Core to make this feature work, removing this class should not cause
additional issues in practice.

The configuration option `typo3RequiredColumnsForFiles` is still available, but
the feature has been reimplemented in a different way. Once enabled, the TYPO3
GalleryProcessor is overridden and all files that do not have the required column
values assigned are removed from the processed output.

In addition, a new control icon is added to each `sys_file_reference` record in
backend forms where file references are configured. When hovering over this icon,
editors can see which required columns still need to be completed.

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
