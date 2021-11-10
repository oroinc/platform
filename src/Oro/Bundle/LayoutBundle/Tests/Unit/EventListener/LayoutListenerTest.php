<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\EventListener\LayoutListener;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\BlockViewNotFoundException;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LayoutListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutManager|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var LayoutListener */
    private $listener;

    protected function setUp(): void
    {
        $this->layoutManager = $this->createMock(LayoutManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $container = TestContainerBuilder::create()
            ->add(LayoutManager::class, $this->layoutManager)
            ->add(LoggerInterface::class, $this->logger)
            ->getContainer($this);

        $this->listener = new LayoutListener($container);
    }

    public function testShouldNotModifyResponseWithoutLayoutAnnotation(): void
    {
        $this->layoutManager->expects(self::never())
            ->method('getLayoutBuilder');
        $responseEvent = $this->createResponseForControllerResultEvent([], []);
        $this->listener->onKernelView($responseEvent);
        self::assertFalse($responseEvent->hasResponse());
    }

    public function testShouldAddOptionsFromLayoutAnnotationToContext(): void
    {
        $builder = $this->createMock(LayoutBuilderInterface::class);

        $builder->expects(self::once())
            ->method('setBlockTheme')
            ->with(['blockTheme1.html.twig', 'blockTheme2.html.twig']);

        $this->setupLayoutExpectations(
            $builder,
            function (ContextInterface $context) {
                self::assertEquals('action', $context->get('action'));
                self::assertEquals('theme', $context->get('theme'));
                self::assertEquals('value1', $context->get('var1'));
                self::assertEquals('value2', $context->get('var2'));
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
        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            [
                'var1' => 'value1',
                'var2' => 'value2'
            ]
        );
        $this->listener->onKernelView($responseEvent);
        self::assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldAddBlockThemeFromLayoutAnnotation(): void
    {
        $builder = $this->createMock(LayoutBuilderInterface::class);

        $builder->expects(self::once())
            ->method('setBlockTheme')
            ->with('blockTheme.html.twig');

        $this->setupLayoutExpectations($builder);

        $layoutAnnotation = new LayoutAnnotation(
            [
                'blockTheme' => 'blockTheme.html.twig'
            ]
        );
        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            []
        );
        $this->listener->onKernelView($responseEvent);
        self::assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldAddOneBlockThemeFromLayoutAnnotationBlockThemesAttr(): void
    {
        $builder = $this->createMock(LayoutBuilderInterface::class);

        $builder->expects(self::once())
            ->method('setBlockTheme')
            ->with('blockTheme.html.twig');

        $this->setupLayoutExpectations($builder);

        $layoutAnnotation = new LayoutAnnotation(
            [
                'blockThemes' => 'blockTheme.html.twig'
            ]
        );
        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            []
        );
        $this->listener->onKernelView($responseEvent);
        self::assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldReturnBlocksContent(): void
    {
        $blocks = [
            'block1' => 'Test block 1',
            'block2' => 'Test block 2',
        ];

        $builder = $this->createMock(LayoutBuilderInterface::class);
        $this->setupLayoutExpectations($builder, null, $blocks);

        $layoutAnnotation = new LayoutAnnotation([]);
        $attributes = [
            '_layout' => $layoutAnnotation,
            'layout_block_ids' => array_keys($blocks),
        ];
        $responseEvent = $this->createResponseForControllerResultEvent(
            $attributes,
            []
        );
        $this->listener->onKernelView($responseEvent);
        self::assertEquals(json_encode($blocks), $responseEvent->getResponse()->getContent());
    }

    public function testShouldReturnAvailableBlocksContentWhenUnknownBlocksRequested(): void
    {
        $builder = $this->createMock(LayoutBuilderInterface::class);
        $this->layoutManager->expects(self::any())
            ->method('getLayoutBuilder')
            ->willReturn($builder);

        $layout = $this->createMock(Layout::class);
        $layout->expects(self::once())
            ->method('render')
            ->willReturn('Test block 1');

        $exception = new BlockViewNotFoundException();
        $this->layoutManager->expects(self::exactly(2))
            ->method('getLayout')
            ->willReturnCallback(static function ($context, $blockId) use ($layout, $exception) {
                if ($blockId === 'block1') {
                    return $layout;
                }

                throw $exception;
            });

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Unknown block "block2" was requested via layout_block_ids',
                ['exception' => $exception]
            );

        $layoutAnnotation = new LayoutAnnotation([]);
        $attributes = [
            '_layout' => $layoutAnnotation,
            'layout_block_ids' => ['block1', 'block2'],
        ];
        $responseEvent = $this->createResponseForControllerResultEvent(
            $attributes,
            []
        );
        $this->listener->onKernelView($responseEvent);
        self::assertEquals(json_encode(['block1' => 'Test block 1']), $responseEvent->getResponse()->getContent());
    }

    public function testShouldThrowExceptionIfBothLayoutAndTemplateAreUsed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The @Template() annotation cannot be used together with the @Layout() annotation.'
        );

        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => new LayoutAnnotation([]), '_template' => new Template([])],
            []
        );
        $this->listener->onKernelView($responseEvent);
    }

    public function testShouldThrowExceptionForMissingVarsInAnnotation(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to resolve the context variables. Reason: The option "unknown" does not exist.'
        );

        $this->setupLayoutExpectations();

        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => new LayoutAnnotation(['vars' => ['known']])],
            ['unknown' => 'data']
        );
        $this->listener->onKernelView($responseEvent);
    }

    public function testShouldThrowExceptionForNotHavingRequiredVarsWhenArrayReturned(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to resolve the context variables. Reason: The required option "required2" is missing.'
        );

        $this->setupLayoutExpectations();

        $attributes = ['_layout' => new LayoutAnnotation(['vars' => ['required1', 'required2']])];
        $result = ['required1' => 'value1'];
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $result);
        $this->listener->onKernelView($responseEvent);
    }

    public function testShouldThrowExceptionForNotHavingRequiredVarsWhenContextReturned(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to resolve the context variables. Reason: The required option "required1" is missing.'
        );

        $this->setupLayoutExpectations();

        $attributes = ['_layout' => new LayoutAnnotation(['vars' => ['required1', 'required2']])];
        $context = new LayoutContext();
        $context->getResolver()->setRequired(['required2']);
        $context['required2'] = 'value1';
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $context);
        $this->listener->onKernelView($responseEvent);
    }

    /**
     * @dataProvider getNotEmptyAnnotationDataProvider
     */
    public function testShouldThrowExceptionWhenAlreadyBuiltLayoutReturnedAndLayoutAnnotationIsNotEmpty(
        array $options
    ): void {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The empty @Layout() annotation must be used when the controller returns an instance of "%s".',
            Layout::class
        ));

        $attributes = ['_layout' => new LayoutAnnotation($options)];
        $layout = $this->createMock(Layout::class);
        $responseEvent = $this->createResponseForControllerResultEvent($attributes, $layout);
        $this->listener->onKernelView($responseEvent);
    }

    public function getNotEmptyAnnotationDataProvider(): array
    {
        return [
            [['action' => 'action']],
            [['theme' => 'theme']],
            [['blockThemes' => ['blockTheme.html.twig']]],
            [['blockTheme' => 'blockTheme.html.twig']],
            [['vars' => ['var1']]],
        ];
    }

    public function testShouldNotOverrideActionFromLayoutAnnotation(): void
    {
        $this->setupLayoutExpectations(
            null,
            function (ContextInterface $context) {
                self::assertEquals('updated_action', $context->get('action'));
            }
        );

        $layoutAnnotation = new LayoutAnnotation(
            [
                'action' => 'default_action'
            ]
        );
        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            [
                'action' => 'updated_action'
            ]
        );
        $this->listener->onKernelView($responseEvent);
        self::assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    public function testShouldNotOverrideThemeFromLayoutAnnotation(): void
    {
        $this->setupLayoutExpectations(
            null,
            function (ContextInterface $context) {
                self::assertEquals('updated_theme', $context->get('theme'));
            }
        );

        $layoutAnnotation = new LayoutAnnotation(
            [
                'theme' => 'default_theme'
            ]
        );
        $responseEvent = $this->createResponseForControllerResultEvent(
            ['_layout' => $layoutAnnotation],
            [
                'theme' => 'updated_theme'
            ]
        );
        $this->listener->onKernelView($responseEvent);
        self::assertEquals('Test Layout', $responseEvent->getResponse()->getContent());
    }

    private function setupLayoutExpectations(
        ?LayoutBuilderInterface $builder = null,
        \Closure $assertContextCallback = null,
        array $renderBlocks = []
    ): void {
        if (null === $builder) {
            $builder = $this->createMock(LayoutBuilderInterface::class);
        }
        $callCount = $renderBlocks ? count($renderBlocks) : 1;
        $this->layoutManager->expects(self::any())
            ->method('getLayoutBuilder')
            ->willReturn($builder);

        $this->layoutManager->expects(self::exactly($callCount))
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
                        $assertContextCallback($context);
                    }

                    return $this->getLayoutMock($renderBlocks, $blockId);
                }
            );
    }

    private function getLayoutMock(
        array $renderBlocks,
        ?string $blockId
    ): \PHPUnit\Framework\MockObject\MockObject|Layout {
        $renderContent = 'Test Layout';
        if ($blockId) {
            $renderContent = $renderBlocks[$blockId] ?? '';
        }
        $layout = $this->createMock(Layout::class);
        $layout->expects(self::once())
            ->method('render')
            ->willReturn($renderContent);
        $layout->expects(self::any())
            ->method('getView')
            ->willReturn($this->createMock(BlockView::class));

        return $layout;
    }

    private function createResponseForControllerResultEvent(array $attributes, $controllerResult): ViewEvent
    {
        return new ViewEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request([], [], $attributes),
            HttpKernelInterface::MASTER_REQUEST,
            $controllerResult
        );
    }
}
