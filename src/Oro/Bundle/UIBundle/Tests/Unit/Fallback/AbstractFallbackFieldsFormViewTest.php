<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fallback;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AbstractFallbackFieldsFormViewTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var BeforeListRenderEvent|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** @var FallbackFieldsFormViewStub|\PHPUnit\Framework\MockObject\MockObject */
    protected $fallbackFieldsFormView;

    /** @var ScrollData|\PHPUnit\Framework\MockObject\MockObject */
    protected $scrollData;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->event = $this->createMock(BeforeListRenderEvent::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.trans';
            });

        $this->fallbackFieldsFormView = new FallbackFieldsFormViewStub(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
        $this->scrollData = $this->createMock(ScrollData::class);
    }

    public function testAddBlockToEntityView()
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->once())
            ->method('render')
            ->willReturn('Rendered template');
        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);
        $this->scrollData->expects($this->once())
            ->method('addSubBlockData');
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->scrollData);

        $this->fallbackFieldsFormView->addBlockToEntityView(
            $this->event,
            'fallbackView.html.twig',
            new ProductStub()
        );
    }

    public function testAddBlockToEntityViewWithSectionTitle()
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->once())
            ->method('render')
            ->willReturn('Rendered template');
        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);
        $this->scrollData->expects($this->once())
            ->method('getData')
            ->willReturn(
                [ScrollData::DATA_BLOCKS => [1 => [ScrollData::TITLE => 'oro.product.sections.inventory.trans']]]
            );
        $this->scrollData->expects($this->once())
            ->method('addSubBlockData');
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->scrollData);

        $this->fallbackFieldsFormView->addBlockToEntityView(
            $this->event,
            'fallbackView.html.twig',
            new ProductStub(),
            'oro.product.sections.inventory'
        );
    }

    public function testAddBlockToEntityEdit()
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->once())
            ->method('render')
            ->willReturn('Rendered template');
        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);
        $this->scrollData->expects($this->once())
            ->method('getData')
            ->willReturn(
                ['dataBlocks' => [1 => ['title' => 'oro.catalog.sections.default_options.trans']]]
            );
        $this->scrollData->expects($this->once())
            ->method('addSubBlockData');
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->scrollData);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.trans';
            });

        $this->fallbackFieldsFormView->addBlockToEntityEdit(
            $this->event,
            'fallbackView.html.twig',
            'oro.catalog.sections.default_options'
        );
    }

    public function testGetEntityFromRequest()
    {
        $currentRequest = $this->createMock(Request::class);
        $currentRequest->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($currentRequest);
        $em = $this->createMock(EntityManager::class);
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
