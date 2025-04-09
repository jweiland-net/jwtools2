<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\AbstractFileFolder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Split file reference into file parts.
 */
class SplitFileRefViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'file',
            'object',
            'File object',
            true,
        );
        $this->registerArgument(
            'as',
            'string',
            'The name of the variable with file parts',
            false,
            'fileParts',
        );
    }

    public function render(): string
    {
        $templateVariableContainer = $renderingContext->getVariableProvider();
        $file = $this->arguments['file'];

        // get Resource Object (non ExtBase version)
        if (is_callable([$file, 'getOriginalResource'])) {
            // We have a domain model, so we need to fetch the FAL resource object from there
            $file = $file->getOriginalResource();
        }

        if (!$file instanceof FileInterface && !$file instanceof AbstractFileFolder) {
            throw new \UnexpectedValueException(
                'Supplied file object type ' . get_class($file) . ' must be FileInterface or AbstractFileFolder.',
                1563891998,
            );
        }

        $fileParts = GeneralUtility::split_fileref($file->getPublicUrl());
        $templateVariableContainer->add($this->arguments['as'], $fileParts);
        $templateVariableContainer->remove($this->arguments['as']);

        return $this->renderChildren();
    }
}
