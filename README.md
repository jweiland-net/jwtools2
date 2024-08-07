# TYPO3 Extension `jwtools2`

[![Latest Stable Version](https://poser.pugx.org/jweiland/jwtools2/v/stable.svg)](https://packagist.org/packages/jweiland/jwtools2)
[![TYPO3 12.4](https://img.shields.io/badge/TYPO3-12.4-green.svg)](https://get.typo3.org/version/12)
[![License](http://poser.pugx.org/jweiland/jwtools2/license)](https://packagist.org/packages/jweiland/jwtools2)
[![Total Downloads](https://poser.pugx.org/jweiland/jwtools2/downloads.svg)](https://packagist.org/packages/jweiland/jwtools2)
[![Monthly Downloads](https://poser.pugx.org/jweiland/jwtools2/d/monthly)](https://packagist.org/packages/jweiland/jwtools2)
![Build Status](https://github.com/jweiland-net/jwtools2/actions/workflows/testscorev12.yml/badge.svg)

Implement various helpful features to TYPO3 and some extensions

# 1 Features

After installing this extension nothing really happens. It's up to you to activate individual
features in Extension Settings.

* Enable page UID in PageTree.
* Transfer value of `current` from CONTENT-Object to renderObj-Object.
* Select categories of current PageTree only.
* Use our task to execute your own individual SQL-Query.
* Html VH with record-attribute which should be used while processing lib.parseFunc
* VH which brings you GeneralUtility::splitFileRef functionality into FE.
* Solr-Features: Use task to index all of your PageTrees in ONE task.
* Aspect for RouteEnhancer to persist static values

Stay tuned, we will fill up this extension with even more feature in future.

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your Composer based TYPO3 project:

```
composer require jweiland/jwtools2
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install `jwtools2` with the extension manager module.

### 2.2 Minimal setup

1) Install the extension
2) Move over to Extension Settings and activate the features you want
