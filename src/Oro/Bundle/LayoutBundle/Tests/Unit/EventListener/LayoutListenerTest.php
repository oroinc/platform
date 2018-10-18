<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\EventListener\LayoutListener;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class LayoutListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $layoutManager;

    /** @var LayoutListener */
    protected $listener;

    /** @var LayoutHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $layoutHelper;

    protected function setUp()
    {
        $this->layoutManager = $this->getMockBuilder(LayoutManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutHelper = $this->getMockBuilder(LayoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = TestContainerBuilder::create()
            ->add('oro_layout.layout_manager', $this->layoutManager)
            ->getContainer($this);

        $this->listener = new LayoutListener($this->layoutHelper, $container);
    }

    public function testShouldNotModifyResponseWithoutLayoutAnnotation()
    {
        $this->layoutManager->expects($this->never())
            ->method('getLayoutBuilder');
        $responseEvent = $this->createResponseForControllerResultEvent([], []);
        $this->listener->onKernelView($responseEvent);
        $this->assertFalse($responseEvent->hasResponse());
    }

    public function testShouldAddOptionsFromLayoutAnnotationToContext()
    {
        $builder = $this->createMock('Oro\Component\Layout\LayoutBuilderInterface');

        $builder->expects($this->once())
            ->method('setBlockTheme')
            ->with(['blockTheme1.html.twig', 'blockTheme2.html.twig']);

        $this->setupLayoutExpectations(
            $builder,
            function (ContextInterface $context) {
                $this->assertEquals('action', $context->get('action'));
                $this->assertEquals('theme', $context->get('theme'));
                $this->assertEquals('value1', $context->get('var1'));
                $this->assertEquals('value2', $context->get('var2'));
            }
        );

        $layoutAnnotation = new LayoutAnnotation(
            [
                'action'      => 'action',
                'theme'       => 'theme',
                'blockThemes' => ['blockTheme1.html.twig', 'blockTheme2.html.twig'],
                'vars'        => ['var1', 'var2']
            ]
        );
        $responseEvent    = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            [
                'var1' => 'value1',
                'var2' => 'value2'
            ]
        );
        $this->listener->onKernelView($responseEvent);
        $this->assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldAddBlockThemeFromLayoutAnnotation()
    {
        $builder = $this->createMock('Oro\Component\Layout\LayoutBuilderInterface');

        $builder->expects($this->once())
            ->method('setBlockTheme')
            ->with('blockTheme.html.twig');

        $this->setupLayoutExpectations($builder);

        $layoutAnnotation = new LayoutAnnotation(
            [
                'blockTheme' => 'blockTheme.html.twig'
            ]
        );
        $responseEvent    = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            []
        );
        $this->listener->onKernelView($responseEvent);
        $this->assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldAddOneBlockThemeFromLayoutAnnotationBlockThemesAttr()
    {
        $builder = $this->createMock('Oro\Component\Layout\LayoutBuilderInterface');

        $builder->expects($this->once())
            ->method('setBlockTheme')
            ->with('blockTheme.html.twig');

        $this->setupLayoutExpectations($builder);

        $layoutAnnotation = new LayoutAnnotation(
            [
                'blockThemes' => 'blockTheme.html.twig'
            ]
        );
        $responseEvent    = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            []
        );
        $this->listener->onKernelView($responseEvent);
        $this->assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldReturnBlocksContent()
    {
        $blocks = [
            'block1' => 'Test block 1',
            'block2' => 'Test block 2',
        ];

        $builder = $this->createMock('Oro\Component\Layout\LayoutBuilderInterface');
        $this->setupLayoutExpectations($builder, null, $blocks);

        $layoutAnnotation = new LayoutAnnotation([]);
        $attributes = [
            '_layout' => $layoutAnnotation,
            'layout_block_ids' => array_keys($blocks),
        ];
        $responseEvent    = $this->createResponseForControllerResultEvent(
            $attributes,
            []
        );
        $this->listener->onKernelView($responseEvent);
        $this->assertEquals(json_encode($blocks), $responseEvent->getResponse()->getContent());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The @Template() annotation cannot be used together with the @Layout() annotation.
     */
    public function testShouldThrowExceptionIfBothLayoutAndTemplateAreUsed()
    {
        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => new LayoutAnnotation([]), '_template' => new Template([])],
            []
        );
        $this->listener->onKernelView($responseEvent);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The option "unknown" does not exist.
     */
    public function testShouldThrowExceptionForMissingVarsInAnnotation()
    {
        $this->setupLayoutExpectations();

        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => new LayoutAnnotation(['vars' => ['known']])],
            ['unknown' => 'data']
        );
        $this->listener->onKernelView($responseEvent);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The required option "required2" is missing.
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionForNotHavingRequiredVarsWhenArrayReturned()
    {
        $this->setupLayoutExpectations();

        $attributes    = ['_layout' => new LayoutAnnotation(['vars' => ['required1', 'required2']])];
        $result        = ['required1' => 'value1'];
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $result);
        $this->listener->onKernelView($responseEvent);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The required option "required1" is missing.
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionForNotHavingRequiredVarsWhenContextReturned()
    {
        $this->setupLayoutExpectations();

        $attributes = ['_layout' => new LayoutAnnotation(['vars' => ['required1', 'required2']])];
        $context    = new LayoutContext();
        $context->getResolver()->setRequired(['required2']);
        $context['required2'] = 'value1';
        $responseEvent        = $this->createResponseForControllerResultEvent($attributes, $context);
        $this->listener->onKernelView($responseEvent);
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             getNotEmptyAnnotationDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The empty @Layout() annotation must be used when the controller returns an instance of "Oro\Component\Layout\Layout".
     * @param array $options
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowExceptionWhenAlreadyBuiltLayoutReturnedAndLayoutAnnotationIsNotEmpty(array $options)
    {
        $attributes    = ['_layout' => new LayoutAnnotation($options)];
        $layout        = $this->getMockBuilder('Oro\Component\Layout\Layout')
            ->disableOriginalConstructor()
            ->getMock();
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $layout);
        $this->listener->onKernelView($responseEvent);
    }

    /**
     * @return array
     */
    public function getNotEmptyAnnotationDataProvider()
    {
        return [
            [['action' => 'action']],
            [['theme' => 'theme']],
            [['blockThemes' => ['blockTheme.html.twig']]],
            [['blockTheme' => 'blockTheme.html.twig']],
            [['vars' => ['var1']]]
        ];
    }

    public function testShouldNotOverrideActionFromLayoutAnnotation()
    {
        $this->setupLayoutExpectations(
            null,
            function (ContextInterface $context) {
                $this->assertEquals('updated_action', $context->get('action'));
            }
        );

        $layoutAnnotation = new LayoutAnnotation(
            [
                'action' => 'default_action'
            ]
        );
        $responseEvent    = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            [
                'action' => 'updated_action'
            ]
        );
        $this->listener->onKernelView($responseEvent);
        $this->assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldNotOverrideThemeFromLayoutAnnotation()
    {
        $this->setupLayoutExpectations(
            null,
            function (ContextInterface $context) {
                $this->assertEquals('updated_theme', $context->get('theme'));
            }
        );

        $layoutAnnotation = new LayoutAnnotation(
            [
                'theme' => 'default_theme'
            ]
        );
        $responseEvent    = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            [
                'theme' => 'updated_theme'
            ]
        );
        $this->listener->onKernelView($responseEvent);
        $this->assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    /**
     * @param LayoutBuilderInterface|null $builder
     * @param \Closure|null $assertContextCallback
     * @param array $renderBlocks
     */
    protected function setupLayoutExpectations(
        $builder = null,
        \Closure $assertContextCallback = null,
        array $renderBlocks = []
    ) {
        if (null === $builder) {
            $builder = $this->createMock('Oro\Component\Layout\LayoutBuilderInterface');
        }
        $callCount = $renderBlocks ? count($renderBlocks) : 1;
        $this->layoutManager->expects($this->any())
            ->method('getLayoutBuilder')
            ->willReturn($builder);

        $this->layoutManager->expects($this->exactly($callCount))
            ->method('getLayout')
            ->willReturnCallback(
                function (ContextInterface $context, $blockId) use ($assertContextCallback, $renderBlocks) {
                    if (!$context->isResolved()) {
                        $context->getResolver()
                            ->setDefined(['theme'])
                            ->setDefaults(['action' => '']);
                        $context->resolve();
                    }

                    if (null !== $assertContextCallback) {
                        call_user_func($assertContextCallback, $context);
                    }

                    return $this->getLayoutMock($renderBlocks, $blockId);
                }
            );
    }

    /**
     * @param array $renderBlocks
     * @param string $blockId
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLayoutMock($renderBlocks, $blockId)
    {
        $renderContent = 'Test Layout';
        if ($blockId) {
            $renderContent = isset($renderBlocks[$blockId]) ? $renderBlocks[$blockId] : '';
        }
        $layout = $this->getMockBuilder('Oro\Component\Layout\Layout')
            ->disableOriginalConstructor()
            ->getMock();
        $layout->expects($this->once())
            ->method('render')
            ->willReturn($renderContent);
        $layout->expects($this->any())
            ->method('getView')
            ->will($this->returnValue($this->createMock('Oro\Component\Layout\BlockView')));

        return $layout;
    }

    /**
     * @param array $attributes
     * @param mixed $controllerResult
     *
     * @return GetResponseForControllerResultEvent
     */
    protected function createResponseForControllerResultEvent(array $attributes, $controllerResult)
    {
        $annotation = null;
        if (array_key_exists('_layout', $attributes)) {
            $annotation = $attributes['_layout'];
        }

        $this->layoutHelper->expects($this->once())
            ->method('getLayoutAnnotation')
            ->willReturn($annotation);

        /** @var HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        return new GetResponseForControllerResultEvent(
            $kernel,
            new Request([], [], $attributes),
            HttpKernelInterface::MASTER_REQUEST,
            $controllerResult
        );
    }
}
