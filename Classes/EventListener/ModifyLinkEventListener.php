<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use JWeiland\Jwtools2\LinkHandler\FileLinkHandler;
use TYPO3\CMS\Backend\Controller\Event\ModifyLinkHandlersEvent;

final class ModifyLinkEventListener
{
    public function __invoke(ModifyLinkHandlersEvent $event): void
    {
        $this->overrideFileLinkHandler($event);
    }

    private function overrideFileLinkHandler($event): void
    {
        $fileHandler = $event->getLinkHandler('file.');
        if ($fileHandler['handler'] === 'TYPO3\CMS\Filelist\LinkHandler\FileLinkHandler') {
            $fileHandler['handler'] = FileLinkHandler::class;
        }
        $event->setLinkHandler('file.', $fileHandler);
    }
}
