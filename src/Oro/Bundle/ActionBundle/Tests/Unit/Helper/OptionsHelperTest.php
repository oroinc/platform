<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;

class OptionsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var Router|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var OptionsHelper */
    protected $helper;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockTranslator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockTranslator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')->getMock();

        $this->helper = new OptionsHelper(
            $this->router,
            $this->mockTranslator
        );
    }

    /**
     * @param ButtonInterface $button
     * @param array $expectedData
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
        return [
            'empty context and parameters' => [
                'button' => $this->getButton('test_button', 'test label', []),
                'expectedData' => [
                    'options' => [
                        'hasDialog' => true,
                        'showDialog' => false,
                        'dialogOptions' => [
                            'title' => null,
                            'dialogOptions' => [],
                        ],
                        'dialogUrl' => null,
                        'executionUrl' => null,
                        'url' => null,
                    ],
                    'data' => [],

                ],
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $templateData
     * @return ButtonInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getButton($name, $label, array $templateData)
    {
        $button = $this->getMock(ButtonInterface::class);
        $templateData = array_merge(
            [
                'executionRoute' => 'test_route',
                'dialogRoute' => 'test_route',
                'hasForm' => true,
                'routeParams' => [],
                'frontendOptions' => [],
                'buttonOptions' => [],
            ],
            $templateData
        );

        $button->expects($this->any())->method('getTemplateData')->willReturn($templateData);
        $button->expects($this->any())->method('getName')->willReturn($name);
        $button->expects($this->any())->method('getLabel')->willReturn($label);

        return $button;
    }
}
