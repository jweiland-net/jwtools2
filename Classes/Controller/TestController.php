<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;

/**
 * Class TestController
 */
class TestController extends AbstractController
{
    /**
     * @var array
     */
    protected $prototypeConfiguration = [
        'formElementsDefinition' => [
            'Page' => [
                'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\Page',
            ],
            'Textfield' => [
                'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement',
            ],
        ],
    ];

    /**
     * Test form
     */
    public function formAction()
    {
        $formDefinition = GeneralUtility::makeInstance(
            FormDefinition::class,
            'test',
            $this->prototypeConfiguration
        );
        $page = $formDefinition->createPage('first');
        $text = GeneralUtility::makeInstance(GenericFormElement::class);
        $page->addElement($text);
        $form = $formDefinition->bind(
            $this->getControllerContext()->getRequest(),
            $this->getControllerContext()->getResponse()
        );
        $this->view->assign('content', $form->render());
    }
}
