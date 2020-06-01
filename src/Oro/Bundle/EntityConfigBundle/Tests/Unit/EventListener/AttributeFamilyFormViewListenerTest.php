<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\EventListener\AttributeFamilyFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AttributeFamilyFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $environment;

    /**
     * @var \Oro\Bundle\EntityConfigBundle\EventListener\AttributeFamilyFormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->environment = $this->createMock(Environment::class);

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
        $formView = new FormView();
        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                'OroEntityConfigBundle:AttributeFamily:familyField.html.twig',
                ['form' => $formView]
            )
            ->willReturn($templateData);

        $event = new BeforeListRenderEvent($this->environment, new ScrollData(), new \stdClass(), $formView);
        $this->listener->onEdit($event);

        $this->assertEquals($expectedScrollData, $event->getScrollData()->getData());
    }
}
