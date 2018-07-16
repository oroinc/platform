<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fallback;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Tests\Unit\Fallback\ProductStub;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class AbstractFallbackFieldsFormViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @var BeforeListRenderEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    /**
     * @var  FallbackFieldsFormViewStub|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fallbackFieldsFormView;

    /**
     * @var  ScrollData|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scrollData;

    protected function setUp()
    {
        parent::setUp();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );
        $this->fallbackFieldsFormView = new FallbackFieldsFormViewStub(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
        $this->scrollData = $this->createMock(ScrollData::class);
    }

    public function testAddBlockToEntityView()
    {
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $env->expects($this->once())->method('render')->willReturn('Rendered template');
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $this->scrollData->expects($this->once())->method('addSubBlockData');
        $this->event->expects($this->once())->method('getScrollData')->willReturn($this->scrollData);

        $this->fallbackFieldsFormView->addBlockToEntityView(
            $this->event,
            'fallbackView.html.twig',
            new ProductStub()
        );
    }

    public function testAddBlockToEntityViewWithSectionTitle()
    {
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $env->expects($this->once())->method('render')->willReturn('Rendered template');
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $this->scrollData->expects($this->once())
            ->method('getData')
            ->willReturn(
                [
                    ScrollData::DATA_BLOCKS => [1 => [ScrollData::TITLE => 'oro.product.sections.inventory.trans']],
                ]
            );
        $this->scrollData->expects($this->once())->method('addSubBlockData');
        $this->event->expects($this->once())->method('getScrollData')->willReturn($this->scrollData);

        $this->fallbackFieldsFormView->addBlockToEntityView(
            $this->event,
            'fallbackView.html.twig',
            new ProductStub(),
            'oro.product.sections.inventory'
        );
    }

    public function testAddBlockToEntityEdit()
    {
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $env->expects($this->once())->method('render')->willReturn('Rendered template');
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $this->scrollData->expects($this->once())->method('getData')->willReturn(
            ['dataBlocks' => [1 => ['title' => 'oro.catalog.sections.default_options.trans']]]
        );
        $this->scrollData->expects($this->once())->method('addSubBlockData');
        $this->event->expects($this->once())->method('getScrollData')->willReturn($this->scrollData);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->fallbackFieldsFormView->addBlockToEntityEdit(
            $this->event,
            'fallbackView.html.twig',
            'oro.catalog.sections.default_options'
        );
    }

    public function testGetEntityFromRequest()
    {
        $currentRequest = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currentRequest->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($currentRequest);
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getReference')
            ->willReturn(ProductStub::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductStub::class)
            ->willReturn($em);
        $this->fallbackFieldsFormView->getEntityFromRequest(ProductStub::class);
    }
}
