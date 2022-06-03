<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Format;

use JWeiland\Jwtools2\ViewHelpers\Format\HtmlViewHelper;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Typolink\ExternalUrlLinkBuilder;

/**
 * Test case
 */
class HtmlViewHelperTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var HtmlViewHelper
     */
    protected $subject;

    /**
     * @var ConfigurationManagerInterface|ObjectProphecy
     */
    protected $configurationManagerProphecy;

    /**
     * @var ObjectManagerInterface|ObjectProphecy
     */
    protected $objectManagerProphecy;

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'frontend',
    ];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/jwtools2',
    ];

    /**
     * @var array
     */
    protected $typoScript = [
        'lib.' => [
            'parseFunc_RTE.' => [
                'allowTags' => 'a,p',
                'denyTags' => '*',
                'makelinks' => '1',
                'makelinks.' => [
                    'http' => [
                        'keep' => 'path',
                    ],
                ],
                'tags.' => [
                    'a' => 'TEXT',
                    'a.' => [
                        'current' => '1',
                        'typolink.' => [
                            'parameter.' => [
                                'data' => 'parameters:href',
                            ],
                            'ATagParams.' => [
                                'data' => 'parameters:allParams',
                            ],
                        ],
                    ],
                ],
                'nonTypoTagStdWrap.' => [
                    'setContentToCurrent' => '1',
                    'cObject' => 'CASE',
                    'cObject.' => [
                        'key.' => [
                            'field' => 'colPos',
                        ],
                        'default' => 'TEXT',
                        'default.' => [
                            'current' => '1',
                            'HTMLparser' => '1',
                            'HTMLparser.' => [
                                'tags.' => [
                                    'a.' => [
                                        'fixAttrib.' => [
                                            'style.' => [
                                                'always' => '1',
                                                'set' => 'color: blue;',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '2' => 'TEXT',
                        '2.' => [
                            'current' => '1',
                            'HTMLparser' => '1',
                            'HTMLparser.' => [
                                'tags.' => [
                                    'a.' => [
                                        'fixAttrib.' => [
                                            'style.' => [
                                                'always' => '1',
                                                'set' => 'color: red;',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $this->configurationManagerProphecy
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->shouldBeCalled()
            ->willReturn($this->typoScript);

        $this->objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $this->objectManagerProphecy
            ->get(ConfigurationManagerInterface::class)
            ->shouldBeCalled()
            ->willReturn($this->configurationManagerProphecy->reveal());

        $linkDetails = [
            'url' => 'https://typo3.org',
            'type' => 'url',
            'typoLinkParameter' => 'https://typo3.org',
        ];

        /** @var ExternalUrlLinkBuilder|ObjectProphecy $linkBuilder */
        $linkBuilder = $this->prophesize(ExternalUrlLinkBuilder::class);
        $linkBuilder
            ->build(
                $linkDetails,
                'Link',
                '',
                [
                    'parameter.' => [
                        'data' => 'parameters:href',
                    ],
                    'ATagParams.' => [
                        'data' => 'parameters:allParams',
                    ],
                ]
            )
            ->shouldBeCalled()
            ->willReturn([
                'https://typo3.org',
                'Link',
                '_blank',
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $this->objectManagerProphecy->reveal());
        GeneralUtility::addInstance(
            ExternalUrlLinkBuilder::class,
            $linkBuilder->reveal()
        );

        $this->subject = new HtmlViewHelper();
        $this->subject->setRenderingContext($this->prophesize(RenderingContext::class)->reveal());
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->objectManagerProphecy,
            $this->configurationManagerProphecy
        );
        GeneralUtility::resetSingletonInstances([]);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function renderWithParseFuncTsPathWillRenderBlueLinks(): void
    {
        $this->subject->setArguments([
            'parseFuncTSPath' => 'lib.parseFunc_RTE',
        ]);

        $this->subject->setRenderChildrenClosure(function () {
            return 'I am a <a href="https://typo3.org">Link</a>';
        });

        if (version_compare(TYPO3_branch, '10.4', '>=')) {
            self::assertSame(
                'I am a <a href="https://typo3.org" target="_blank" rel="noreferrer" style="color: blue;">Link</a>',
                $this->subject->initializeArgumentsAndRender()
            );
        } else {
            self::assertSame(
                'I am a <a href="https://typo3.org" target="_blank" style="color: blue;">Link</a>',
                $this->subject->initializeArgumentsAndRender()
            );
        }
    }

    /**
     * @test
     */
    public function renderWithParseFuncTsPathWillConsiderTsConditionAndRendersRedLinks(): void
    {
        $this->subject->setArguments([
            'parseFuncTSPath' => 'lib.parseFunc_RTE',
            'data' => [
                'colPos' => '2',
            ],
        ]);

        $this->subject->setRenderChildrenClosure(function () {
            return 'I am a <a href="https://typo3.org">Link</a>';
        });

        if (version_compare(TYPO3_branch, '10.4', '>=')) {
            self::assertSame(
                'I am a <a href="https://typo3.org" target="_blank" rel="noreferrer" style="color: red;">Link</a>',
                $this->subject->initializeArgumentsAndRender()
            );
        } else {
            self::assertSame(
                'I am a <a href="https://typo3.org" target="_blank" style="color: red;">Link</a>',
                $this->subject->initializeArgumentsAndRender()
            );
        }
    }
}
