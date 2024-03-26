<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller;

use JWeiland\Jwtools2\Traits\InjectIconFactoryTrait;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Class AbstractController
 */
abstract class AbstractController extends ActionController
{
    use InjectIconFactoryTrait;

    protected ?ModuleTemplate $moduleTemplate = null;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {}

    /**
     * Initializes the view before invoking an action method.
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     */
    protected function initializeView( TemplateView $view): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->assign('extConf', $this->getExtensionConfiguration());
    }

    protected function getExtensionConfiguration(): array
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('jwtools2');
        return is_array($extensionConfiguration) ? $extensionConfiguration : [];
    }
}
