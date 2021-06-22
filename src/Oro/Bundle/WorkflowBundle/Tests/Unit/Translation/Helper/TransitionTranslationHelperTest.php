<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\Helper;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class TransitionTranslationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TransitionTranslationHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->helper = new TransitionTranslationHelper($this->translator);
    }

    /**
     * @dataProvider processTransitionTranslationsProvider
     */
    public function testProcessTransitionTranslations(array $inputData, array $expectedData)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnMap($inputData['translates']);

        $transition = $this->getMockBuilder(Transition::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__construct'])
            ->getMock();

        $transition->setLabel($inputData['label']);
        $transition->setButtonLabel($inputData['buttonLabel']);
        $transition->setButtonTitle($inputData['buttonTitle']);
        $transition->setFrontendOptions($inputData['frontendOptions']);

        $this->helper->processTransitionTranslations($transition);

        $this->assertEquals($expectedData['buttonLabel'], $transition->getButtonLabel());
        $this->assertEquals($expectedData['buttonTitle'], $transition->getButtonTitle());
        $this->assertEquals($expectedData['frontendOptions'], $transition->getFrontendOptions());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processTransitionTranslationsProvider(): array
    {
        return [
            'full labels' => [
                'input' => [
                    'translates' => [
                        ['UntranslatedLabel', [], 'workflows', null, 'TranslatedLabel'],
                        ['UntranslatedButtonLabel', [], 'workflows', null, 'TranslatedButtonLabel'],
                        ['UntranslatedButtonTitle', [], 'workflows', null, 'TranslatedButtonTitle'],
                    ],
                    'label' => 'UntranslatedLabel',
                    'buttonLabel' => 'UntranslatedButtonLabel',
                    'buttonTitle' => 'UntranslatedButtonTitle',
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'ExistingTitle',
                        ],
                    ]
                ],
                'expected' => [
                    'buttonLabel' => 'TranslatedButtonLabel',
                    'buttonTitle' => 'TranslatedButtonTitle',
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'ExistingTitle',
                        ],
                    ]
                ],
            ],
            'full labels without predefined title' => [
                'input' => [
                    'translates' => [
                        ['UntranslatedLabel', [], 'workflows', null, 'TranslatedLabel'],
                        ['UntranslatedButtonLabel', [], 'workflows', null, 'TranslatedButtonLabel'],
                        ['UntranslatedButtonTitle', [], 'workflows', null, 'TranslatedButtonTitle'],
                    ],
                    'label' => 'UntranslatedLabel',
                    'buttonLabel' => 'UntranslatedButtonLabel',
                    'buttonTitle' => 'UntranslatedButtonTitle',
                    'frontendOptions' => []
                ],
                'expected' => [
                    'buttonLabel' => 'TranslatedButtonLabel',
                    'buttonTitle' => 'TranslatedButtonTitle',
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'TranslatedButtonLabel',
                        ],
                    ]
                ],
            ],
            'no translate for button title' => [
                'input' => [
                    'translates' => [
                        ['UntranslatedLabel', [], 'workflows', null, 'TranslatedLabel'],
                        ['UntranslatedButtonLabel', [], 'workflows', null, 'TranslatedButtonLabel'],
                        ['UntranslatedButtonTitle', [], 'workflows', null, 'UntranslatedButtonTitle'],
                    ],
                    'label' => 'UntranslatedLabel',
                    'buttonLabel' => 'UntranslatedButtonLabel',
                    'buttonTitle' => 'UntranslatedButtonTitle',
                    'frontendOptions' => []
                ],
                'expected' => [
                    'buttonLabel' => 'TranslatedButtonLabel',
                    'buttonTitle' => null,
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'TranslatedButtonLabel',
                        ],
                    ]
                ],
            ],
            'no translate for button label' => [
                'input' => [
                    'translates' => [
                        ['UntranslatedLabel', [], 'workflows', null, 'TranslatedLabel'],
                        ['UntranslatedButtonLabel', [], 'workflows', null, 'UntranslatedButtonLabel'],
                        ['UntranslatedButtonTitle', [], 'workflows', null, 'UntranslatedButtonTitle'],
                    ],
                    'label' => 'UntranslatedLabel',
                    'buttonLabel' => 'UntranslatedButtonLabel',
                    'buttonTitle' => 'UntranslatedButtonTitle',
                    'frontendOptions' => []
                ],
                'expected' => [
                    'buttonLabel' => 'TranslatedLabel',
                    'buttonTitle' => null,
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'TranslatedLabel',
                        ],
                    ]
                ],
            ],
            'empty button title' => [
                'input' => [
                    'translates' => [
                        ['UntranslatedLabel', [], 'workflows', null, 'TranslatedLabel'],
                        ['UntranslatedButtonLabel', [], 'workflows', null, 'TranslatedButtonLabel'],
                        ['UntranslatedButtonTitle', [], 'workflows', null, ''],
                    ],
                    'label' => 'UntranslatedLabel',
                    'buttonLabel' => 'UntranslatedButtonLabel',
                    'buttonTitle' => 'UntranslatedButtonTitle',
                    'frontendOptions' => []
                ],
                'expected' => [
                    'buttonLabel' => 'TranslatedButtonLabel',
                    'buttonTitle' => null,
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'TranslatedButtonLabel',
                        ],
                    ]
                ],
            ],
            'empty button label' => [
                'input' => [
                    'translates' => [
                        ['UntranslatedLabel', [], 'workflows', null, 'TranslatedLabel'],
                        ['UntranslatedButtonLabel', [], 'workflows', null, ''],
                        ['UntranslatedButtonTitle', [], 'workflows', null, ''],
                    ],
                    'label' => 'UntranslatedLabel',
                    'buttonLabel' => 'UntranslatedButtonLabel',
                    'buttonTitle' => 'UntranslatedButtonTitle',
                    'frontendOptions' => []
                ],
                'expected' => [
                    'buttonLabel' => 'TranslatedLabel',
                    'buttonTitle' => null,
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'TranslatedLabel',
                        ],
                    ]
                ],
            ],
        ];
    }
}
