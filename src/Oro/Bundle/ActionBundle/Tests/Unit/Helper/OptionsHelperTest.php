<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;

use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;

class OptionsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var Router|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var FormProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $formProvider;

    /** @var OptionsHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router->expects($this->any())->method('generate')->willReturn('generated-url');
        $this->formProvider = $this->createMock(FormProvider::class);

        $this->helper = new OptionsHelper(
            $this->router,
            new StubTranslator()
        );
        $this->helper->setFormProvider($this->formProvider);
    }

    /**
     * @param ButtonInterface $button
     * @param array $expectedData
     *
     * @dataProvider getFrontendOptionsProvider
     */
    public function testGetFrontendOptions(ButtonInterface $button, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->helper->getFrontendOptions($button));
    }

    /**
     * @return array
     */
    public function getFrontendOptionsProvider()
    {
        $defaultData = [
            'options' => [
                'hasDialog' => null,
                'showDialog' => false,
                'executionUrl' => 'generated-url',
                'url' => 'generated-url',
            ],
            'data' => [],
        ];

        return [
            'empty options' => [
                'button' => $this->getButton('test label', []),
                'expectedData' => $defaultData,
            ],
            'filled options' => [
                'button' => $this->getButton('test label', [
                    'hasForm' => true,
                    'hasDialog' => true,
                    'showDialog' => true,
                    'frontendOptions' => [
                        'title' => 'custom title',
                    ],
                    'buttonOptions' => [
                        'data' => [
                            'some' => 'data',
                        ],
                    ],
                ]),
                'expectedData' => [
                    'options' => [
                        'hasDialog' => true,
                        'showDialog' => true,
                        'dialogOptions' => [
                            'title' => '[trans]custom title[/trans]',
                            'dialogOptions' => [],
                        ],
                        'dialogUrl' => 'generated-url',
                        'executionUrl' => 'generated-url',
                        'url' => 'generated-url',
                    ],
                    'data' => [
                        'some' => 'data',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $label
     * @param array $templateData
     *
     * @return ButtonInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getButton($label, array $templateData)
    {
        $button = $this->createMock(ButtonInterface::class);
        $button->expects($this->any())->method('getTemplateData')->willReturn($templateData);
        $button->expects($this->any())->method('getLabel')->willReturn($label);

        return $button;
    }
}
