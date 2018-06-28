<?php
namespace JWeiland\Jwtools2\ConfigurationType;

/*
 * This file is part of the jwtools2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Form\Element\SelectMultipleSideBySideElement;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper;

/**
 * Class TablesField
 */
class TablesField
{
    /**
     * Returns html for field
     *
     * @param array $params
     * @param TypoScriptConstantsViewHelper $viewHelper
     * @return string
     */
    public function render(array $params, TypoScriptConstantsViewHelper $viewHelper)
    {
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

        /** @var ConnectionPool $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $schemaManager = $connection->getConnectionForTable('pages')->getSchemaManager();
        $items = [];

        foreach ($schemaManager->listTableNames() as $tableName) {
            $items[] = [$tableName, $tableName];
        }

        /** @var SelectMultipleSideBySideElement $node */
        $node = $nodeFactory->create([
            'renderType' => 'selectMultipleSideBySide',
            'parameterArray' => [
                'itemFormElName' => 'tx_extensionmanager_tools_extensionmanagerextensionmanager'
                    .'[config][solrTablesToAddKeywordBoosting][value]',
                'itemFormElValue' => GeneralUtility::trimExplode(',', $params['fieldValue'], true),
                'fieldConf' => [
                    'config' => [
                        'items' => $items,
                        'enableMultiSelectFilterTextfield' => true
                    ]
                ],
                'fieldChangeFunc' => [
                    'TBE_EDITOR.fieldChanged('
                        . GeneralUtility::quoteJSvalue('config') . ','
                        . GeneralUtility::quoteJSvalue('solrTablesToAddKeywordBoosting') . ','
                        . GeneralUtility::quoteJSvalue('value') . ','
                        . GeneralUtility::quoteJSvalue(
                            'tx_extensionmanager_tools_extensionmanagerextensionmanager'
                            . '[config][solrTablesToAddKeywordBoosting][value]'
                        )
                    . ');'
                ]
            ]
        ]);

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);

        $formResultCompiler->printNeededJSFunctions();

        $pageRenderer->addInlineSetting('FormEngine', 'formName', 'configurationform');

        return $node->render()['html'];
    }
}
