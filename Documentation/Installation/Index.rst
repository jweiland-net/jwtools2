.. include:: /Includes.rst.txt


.. _installation:

============
Installation
============

Extension Manager
=================

Install `jwtools2` in TYPO3 legacy installations with the help of Extension Manager:

..  rst-class:: bignums

1. TYPO3 BE Login

   Login into TYPO3 backend as an administrator or system maintainer.

2. Visit Extension Manager

   Click :guilabel:`Extension Manager` from the left side menu.

3. Get Extension

   Choose :guilabel:`Get Extensions` from the upper selectbox.

4. Search

   Use the search mask to search for `jwtools2`.

5. Install

   Find `jwtools2` in the list and install the extension with the icon on the left.

6. Wait

   The installation will be confirmed with a blue notification on the upper right.


Composer
========

Install `jwtools2` in TYPO3 Composer-based installations on the shell:

..  rst-class:: bignums

1. Shell Login

   Login into shell of your TYPO3 installation.

2. Change Directory

   Move into the root directory of your TYPO3 installation

3. Install

   .. code-block:: bash

      composer req jweiland/jwtools2

4. Add `jwtools2` profile table

   .. tabs::

      .. group-tab:: Composer-based installation

         .. code-block:: bash

            vendor/bin/typo3 extension:setup -e jwtools2

      .. group-tab:: Legacy installation

         .. code-block:: bash

            typo3/sysext/core/bin/typo3 extension:setup -e jwtools2


Next step
=========

Configure `jwtools2` in :ref:`Extension Settings <extensionSettings>`.
