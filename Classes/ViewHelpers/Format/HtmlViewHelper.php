<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\ViewHelpers\Format;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Renders a string by passing it to a TYPO3 `parseFunc`_.
 * You can either specify a path to the TypoScript setting or set the `parseFunc`_ options directly.
 * By default :ts:`lib.parseFunc_RTE` is used to parse the string.
 * SF: This VH is a modified version of the original HtmlViewHelper of EXT:fluid. We have added the data-Attribute
 * to get TS if-conditions in lib.parseFunc work again. We will remove that VH, if this patch will be merged:
 * https://review.typo3.org/c/Packages/TYPO3.CMS/+/66374
 * Examples
 * ========
 * Default parameters
 * ------------------
 * ::
 *    <jw:format.html>
 *        foo <b>bar</b>. Some <a href="t3://page?uid=1">Link</a>.
 *    </jw:format.html>
 * Output::
 *    <p class="bodytext">foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.</p>
 * Depending on TYPO3 setup.
 * Custom parseFunc
 * ----------------
 * ::
 *    <jw:format.html parseFuncTSPath="lib.parseFunc">
 *        foo <b>bar</b>. Some <a href="t3://page?uid=1">Link</a>.
 *    </jw:format.html>
 * Output::
 *    foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.
 * Individual data attribute
 * -------------------------
 * If you work with TS:field property in lib.parseFunc you should add current record to Html VH.
 * ::
 *    <jw:format.html parseFuncTSPath="lib.parseFunc" data="{data}">
 *        foo <b>bar</b>. Some <a href="t3://page?uid=1">Link</a>.
 *    </jw:format.html>
 * Output::
 *    foo <b>bar</b>. Some <a href="index.php?id=1">link</a>.
 * Inline notation
 * ---------------
 * ::
 *    {someText -> jw:format.html(parseFuncTSPath: 'lib.parseFunc')}
 * Output::
 *    foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.
 * .. _parseFunc: https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Functions/Parsefunc.html
 *
 * @deprecated Will be removed while removing TYPO3 10 compatibility. Please use f:format.html which has all options now.
 */
class HtmlViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var TypoScriptFrontendController contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
     */
    protected static $tsfeBackup;

    /**
     * Children must not be escaped, to be able to pass {bodytext} directly to it
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Plain HTML should be returned, no output escaping allowed
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'parseFuncTSPath',
            'string',
            'Path to TypoScript parseFunc setup.',
            false,
            'lib.parseFunc_RTE'
        );
        $this->registerArgument('data', 'array', 'Initialize ContentObjectRenderer with this set of data.', false, []);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $parseFuncTSPath = $arguments['parseFuncTSPath'];
        if (TYPO3_MODE === 'BE') {
            self::simulateFrontendEnvironment();
        }

        $value = $renderChildrenClosure();
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObject->start($arguments['data'] ?? []);
        $content = $contentObject->parseFunc($value, [], '< ' . $parseFuncTSPath);

        if (TYPO3_MODE === 'BE') {
            self::resetFrontendEnvironment();
        }

        return $content;
    }

    /**
     * Copies the specified parseFunc configuration to $GLOBALS['TSFE']->tmpl->setup in Backend mode
     * This somewhat hacky work around is currently needed because the parseFunc() function of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer relies on those variables to be set
     */
    protected static function simulateFrontendEnvironment(): void
    {
        if (
            !$GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            || !$GLOBALS['TSFE']->tmpl instanceof TemplateService
            || !$GLOBALS['TSFE']->sys_page instanceof PageRepository
        ) {
            self::$tsfeBackup = $GLOBALS['TSFE'] ?? null;
            $GLOBALS['TSFE'] = new \stdClass();
            $GLOBALS['TSFE']->cObjectDepthCounter = 50;
            $GLOBALS['TSFE']->tmpl = new \stdClass();
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
            $GLOBALS['TSFE']->tmpl->setup = $configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
            );
        }
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     *
     * @see simulateFrontendEnvironment()
     */
    protected static function resetFrontendEnvironment(): void
    {
        if ($GLOBALS['TSFE'] instanceof \stdClass) {
            $GLOBALS['TSFE'] = self::$tsfeBackup;
        }
    }
}
