<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fallback;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UIBundle\Tests\Unit\Fallback\ProductStub;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class AbstractFallbackFieldsFormViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var  FallbackFieldsFormViewStub|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fallbackFieldsFormView;

    /**
     * @var  ScrollData|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scrollData;

    protected function setUp()
    {
        parent::setUp();
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->fallbackFieldsFormView = new FallbackFieldsFormViewStub(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
        $this->scrollData = $this->getMock(ScrollData::class);
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
