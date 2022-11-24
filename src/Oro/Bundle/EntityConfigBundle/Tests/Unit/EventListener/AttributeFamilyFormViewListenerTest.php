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
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var AttributeFamilyFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.trans';
            });

        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AttributeFamilyFormViewListener($translator);
    }

    public function onEditDataProvider(): array
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
     */
    public function testOnEdit(string $templateData, array $expectedScrollData)
    {
        $formView = new FormView();
        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                '@OroEntityConfig/AttributeFamily/familyField.html.twig',
                ['form' => $formView]
            )
            ->willReturn($templateData);

        $event = new BeforeListRenderEvent($this->environment, new ScrollData(), new \stdClass(), $formView);
        $this->listener->onEdit($event);

        $this->assertEquals($expectedScrollData, $event->getScrollData()->getData());
    }
}
