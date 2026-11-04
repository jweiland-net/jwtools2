<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Middleware;

use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Loads a JavaScript module into the file element browser which prevents the selection of files
 * marked as "not selectable" by IsFileSelectableEventListener.
 *
 * TYPO3 core only hides the checkbox and the "select" button of such files. A click on the
 * file name still triggers the "primary" action, which core forwards to "select" without
 * checking the "data-filelist-selectable" attribute.
 */
final readonly class ElementBrowserSelectionGuardMiddleware implements MiddlewareInterface
{
    private const string ROUTE_IDENTIFIER = 'wizard_element_browser';

    private const string JAVASCRIPT_MODULE = '@jweiland/jwtools2/element-browser-selection-guard.js';

    private const string LANGUAGE_FILE = 'EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf';

    private const string LANGUAGE_LABEL_PREFIX = 'fileBrowser.notification.';

    public function __construct(
        private FileMetaDataValidationService $fileMetaDataValidationService,
        private PageRenderer $pageRenderer,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            $this->isFileElementBrowserPageRequest($request)
            && $this->fileMetaDataValidationService->getRequiredColumns() !== []
        ) {
            $this->pageRenderer->loadJavaScriptModule(self::JAVASCRIPT_MODULE);
            $this->pageRenderer->addInlineLanguageLabelFile(self::LANGUAGE_FILE, self::LANGUAGE_LABEL_PREFIX);
        }

        return $handler->handle($request);
    }

    /**
     * Folder navigation inside the element browser reloads the file list via AJAX ("contentOnly").
     * The JavaScript module is already active in that case, so it only has to be loaded for the full page.
     */
    private function isFileElementBrowserPageRequest(ServerRequestInterface $request): bool
    {
        $route = $request->getAttribute('route');
        $queryParams = $request->getQueryParams();

        return $route instanceof Route
            && $route->getOption('_identifier') === self::ROUTE_IDENTIFIER
            && ($queryParams['mode'] ?? '') === 'file'
            && !(bool)($queryParams['contentOnly'] ?? false);
    }
}
