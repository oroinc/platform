<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\EventListener\LayoutListener;
use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;

class LayoutListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $layoutManager;

    /** @var LayoutListener */
    protected $listener;

    protected function setUp()
    {
        $this->layoutManager = $this->getMockBuilder('Oro\Component\Layout\LayoutManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new LayoutListener($this->layoutManager);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals([KernelEvents::VIEW => 'onKernelView'], LayoutListener::getSubscribedEvents());
    }

    public function testShouldNotModifyResponseWithoutLayoutAnnotation()
    {
        $this->layoutManager->expects($this->never())
            ->method('getLayoutBuilder');
        $responseEvent = $this->createResponseForControllerResultEvent([], []);
        $this->listener->onKernelView($responseEvent);
    }

    public function testShouldAddOptionsFromLayoutAnnotationToContext()
    {
        $builder = $this->getMock('Oro\Component\Layout\LayoutBuilderInterface');
        $this->layoutManager->expects($this->once())
            ->method('getLayoutBuilder')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('setBlockTheme')
            ->with(['test.html.twig']);

        $builder->expects($this->once())
            ->method('getLayout')
            ->willReturnCallback(function(ContextInterface $context) {
                $context->getResolver()->setOptional(['theme', 'blockThemes', 'var1', 'var2']);
                $context->resolve();
                $this->assertEquals('theme', $context->get('theme'));
                $this->assertEquals('value1', $context->get('var1'));
                $this->assertEquals('value2', $context->get('var2'));

                return $this->getMockBuilder('Oro\Component\Layout\Layout')
                    ->disableOriginalConstructor()
                    ->getMock();
            });

        $layoutAnnotation = new LayoutAnnotation(
            [
                'theme'     => 'theme',
                'blockThemes' => ['test.html.twig'],
                'vars'      => ['var1', 'var2']
            ]
        );
        $controllerResult = [
            'var1' => 'value1',
            'var2' => 'value2'
        ];
        $attributes = ['_' . LayoutAnnotation::ALIAS => $layoutAnnotation];
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $controllerResult);
        $this->listener->onKernelView($responseEvent);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The option "unknown" does not exist. Known options are: "known"
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionForMissingVarsInAnnotation()
    {
        $this->setupLayoutExpectations();

        $result = ['unknown' => 'data'];
        $attributes = ['_' . LayoutAnnotation::ALIAS => new LayoutAnnotation(['vars' => ['known']])];
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $result);
        $this->listener->onKernelView($responseEvent);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The required option "required2" is missing.
     */
    public function testShouldThrowExceptionForNotHavingRequiredVarsWhenArrayReturned()
    {
        $this->setupLayoutExpectations();

        $attributes = ['_' . LayoutAnnotation::ALIAS => new LayoutAnnotation(['vars' => ['required1', 'required2']])];
        $result = ['required1' => 'value1'];
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $result);
        $this->listener->onKernelView($responseEvent);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The required option "required1" is missing.
     */
    public function testShouldThrowExceptionForNotHavingRequiredVarsWhenContextReturned()
    {
        $this->setupLayoutExpectations();

        $attributes = ['_' . LayoutAnnotation::ALIAS => new LayoutAnnotation(['vars' => ['required1', 'required2']])];
        $context = new LayoutContext();
        $context->getResolver()->setRequired(['required2']);
        $context['required2'] = 'value1';
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $context);
        $this->listener->onKernelView($responseEvent);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage @Layout annotation configured improperly. Should use empty @Layout() configuration when returning an instance of Oro\Component\Layout\Layout in the response.
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionTryingToRedefineThemeWhenContextReturned()
    {
        $attributes = ['_' . LayoutAnnotation::ALIAS => new LayoutAnnotation(['theme' => 'theme'])];
        $layout = $this->getMockBuilder('Oro\Component\Layout\Layout')
            ->disableOriginalConstructor()
            ->getMock();
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $layout);
        $this->listener->onKernelView($responseEvent);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Layout annotation configured improperly. Cannot redefine context option theme that is already set in the response.
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionIfLayoutAnnotationIsNotEmptyWhenLayoutReturned()
    {
        $attributes = ['_' . LayoutAnnotation::ALIAS => new LayoutAnnotation(['theme' => 'theme_new'])];
        $context = new LayoutContext();
        $context->getResolver()->setRequired(['theme']);
        $context['theme'] = 'theme_old';
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $context);
        $this->listener->onKernelView($responseEvent);
    }

    protected function setupLayoutExpectations()
    {
        $builder = $this->getMock('Oro\Component\Layout\LayoutBuilderInterface');
        $this->layoutManager->expects($this->once())
            ->method('getLayoutBuilder')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('getLayout')
            ->willReturnCallback(function(ContextInterface $context) {
                $context->resolve();

                return $this->getMockBuilder('Oro\Component\Layout\Layout')
                    ->disableOriginalConstructor()
                    ->getMock();
            });
    }

    /**
     * @param array $attributes
     * @param mixed $controllerResult
     *
     * @return GetResponseForControllerResultEvent
     */
    protected function createResponseForControllerResultEvent(array $attributes = [], $controllerResult = [])
    {
        return new GetResponseForControllerResultEvent(
            $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            new Request([], [], $attributes),
            HttpKernelInterface::SUB_REQUEST,
            $controllerResult
        );
    }
}
