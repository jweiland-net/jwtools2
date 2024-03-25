..  include:: /Includes.rst.txt


=======
Upgrade
=======

If you upgrade EXT:jwtools2 to a newer version, please read this section carefully!

Upgrade to Version 7.0.0
========================

..  important::

    This version is not TYPO3 11 compatible!

Removed Option: `typo3UploadFieldsInTopOfEB`
--------------------------------------------

In the latest version of TYPO3, the `typo3UploadFieldsInTopOfEB` option has been removed. This change was necessitated
by the introduction of a paginator in File Abstraction Layer (FAL). As a result, it's no longer possible to adjust the
position of File upload or create fields above the list without Xclass.

Compatibility Fixes and Updates
-------------------------------

- Compatibility fix for TYPO3 12
- Testing Framework migrated to TYPO3 Testing Framework

Deprecated and Obsolete Features
--------------------------------

- Remove support for TYPO3 11 and lower versions
- Removed `jwtools2:executeExtensionUpdate` command line controller as it is obsolete.
- Removed deprecated functions and usages
