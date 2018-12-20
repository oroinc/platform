<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\EventListener\AttributeFamilyFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

class AttributeFamilyFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $environment;

    /**
     * @var \Oro\Bundle\EntityConfigBundle\EventListener\AttributeFamilyFormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->getMock();
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new AttributeFamilyFormViewListener($this->translator);
    }

    /**
     * @return array
     */
    public function onEditDataProvider()
    {
        return [
            'empty template' => [
                'templateData' => '',
                'expectedScrollData' => []
            ],
            'not empty template' => [
                'templateData' => '<div></div>',
                'expectedScrollData' => [
                    'dataBlocks' => [
                        [
                            'title' => 'oro.entity_config.attribute_family.entity_label.trans',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                ['data' => ['<div></div>']]
                            ]
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * @dataProvider onEditDataProvider
     *
     * @param string $templateData
     * @param array $expectedScrollData
     */
    public function testOnEdit($templateData, array $expectedScrollData)
    {
        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                'OroEntityConfigBundle:AttributeFamily:familyField.html.twig',
                ['form' => new FormView()]
            )
            ->willReturn($templateData);

        $event = new BeforeListRenderEvent($this->environment, new ScrollData(), new \stdClass(), new FormView());
        $this->listener->onEdit($event);

        $this->assertEquals($expectedScrollData, $event->getScrollData()->getData());
    }
}
