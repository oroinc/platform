<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fallback;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class AbstractFallbackFieldsFormViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

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
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->fallbackFieldsFormView = new FallbackFieldsFormViewStub(
            $this->requestStack,
            $this->doctrineHelper,
            $this->translator
        );
        $this->scrollData = $this->getMock(ScrollData::class);
    }

    public function testAddBlockToEntityView()
    {
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $env->expects($this->once())->method('render');
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $this->scrollData->expects($this->once())->method('addSubBlockData');
        $this->event->expects($this->once())->method('getScrollData')->willReturn($this->scrollData);

        $this->fallbackFieldsFormView->addBlockToEntityView(
            $this->event,
            'OroInventoryBundle:Product:inventoryThreshold.html.twig',
            new Product()
        );
    }

    public function testAddBlockToEntityEdit()
    {
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $env->expects($this->once())->method('render');
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
            'OroInventoryBundle:Product:inventoryThreshold.html.twig',
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
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($this->getMock(Product::class));
        $this->fallbackFieldsFormView->getEntityFromRequest(Product::class);
    }
}
