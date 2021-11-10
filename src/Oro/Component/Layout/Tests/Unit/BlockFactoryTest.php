<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockFactory;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\Extension\PreloadedExtension;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutRegistry;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\AbstractExtensionStub;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BlockFactoryTest extends LayoutTestCase
{
    /** @var LayoutContext */
    private $context;

    /** @var RawLayoutBuilder */
    private $rawLayoutBuilder;

    /** @var DeferredLayoutManipulator */
    private $layoutManipulator;

    /** @var LayoutRegistry */
    private $registry;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var BlockFactory */
    private $blockFactory;

    protected function setUp(): void
    {
        $this->registry = new LayoutRegistry();
        $this->registry->addExtension(new CoreExtension());
        $this->registry->addExtension(
            new PreloadedExtension(
                [
                    'root'                         => new Type\RootType(),
                    'header'                       => new Type\HeaderType(),
                    'logo'                         => new Type\LogoType(),
                    'logo_with_required_title'     => new Type\LogoWithRequiredTitleType(),
                    'test_self_building_container' => new Type\TestSelfBuildingContainerType()
                ]
            )
        );

        $this->context = new LayoutContext();
        $this->rawLayoutBuilder = new RawLayoutBuilder();
        $this->layoutManipulator = new DeferredLayoutManipulator($this->registry, $this->rawLayoutBuilder);
        $this->expressionLanguage = new ExpressionLanguage();
        $expressionProcessor = new ExpressionProcessor(
            $this->expressionLanguage,
            new ExpressionEncoderRegistry([])
        );
        $this->blockFactory = new BlockFactory(
            $this->registry,
            $this->layoutManipulator,
            $expressionProcessor
        );
    }

    private function getLayoutView(): BlockView
    {
        $this->layoutManipulator->applyChanges($this->context, true);
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();

        return $this->blockFactory->createBlockView($rawLayout, $this->context);
    }

    public function testSimpleLayout()
    {
        $this->context->resolve();

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testCoreVariablesForRootItemOnly()
    {
        $this->context->resolve();

        $this->layoutManipulator
            ->add('rootId', null, 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                   => 'rootId',
                    'block_type'           => 'root',
                    'block_type_widget_id' => 'root_widget',
                    'translation_domain'   => 'messages',
                    'unique_block_prefix'  => '_rootId',
                    'block_prefixes'       => [
                        BaseType::NAME,
                        ContainerType::NAME,
                        'root',
                        '_rootId'
                    ],
                    'cache_key'            => '_rootId_root_ad7b81dea42cf2ef7525c274471e3ce6'
                ],
                'children' => []
            ],
            $view,
            false
        );
    }

    public function testCoreVariables()
    {
        $this->context->resolve();

        $this->layoutManipulator
            ->add('rootId', null, 'root')
            ->add('headerId', 'rootId', 'header')
            ->add('logoId', 'headerId', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                   => 'rootId',
                    'block_type'           => 'root',
                    'block_type_widget_id' => 'root_widget',
                    'translation_domain'   => 'messages',
                    'unique_block_prefix'  => '_rootId',
                    'block_prefixes'       => [
                        BaseType::NAME,
                        ContainerType::NAME,
                        'root',
                        '_rootId'
                    ],
                    'cache_key'            => '_rootId_root_ad7b81dea42cf2ef7525c274471e3ce6'
                ],
                'children' => [
                    [ // header
                        'vars'     => [
                            'id'                   => 'headerId',
                            'block_type'           => 'header',
                            'block_type_widget_id' => 'header_widget',
                            'translation_domain'   => 'messages',
                            'unique_block_prefix'  => '_headerId',
                            'block_prefixes'       => [
                                BaseType::NAME,
                                ContainerType::NAME,
                                'header',
                                '_headerId'
                            ],
                            'cache_key'            => '_headerId_header_ad7b81dea42cf2ef7525c274471e3ce6'
                        ],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'                   => 'logoId',
                                    'block_type'           => 'logo',
                                    'block_type_widget_id' => 'logo_widget',
                                    'translation_domain'   => 'messages',
                                    'unique_block_prefix'  => '_logoId',
                                    'block_prefixes'       => [
                                        BaseType::NAME,
                                        'logo',
                                        '_logoId'
                                    ],
                                    'cache_key'            => '_logoId_logo_ad7b81dea42cf2ef7525c274471e3ce6',
                                    'title'                => 'test'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view,
            false
        );
    }

    public function testAddChildToNotContainer()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The "header" item cannot be added as a child to "logo" item (block type: logo)'
            . ' because only container blocks can have children.'
        );

        $this->context->resolve();

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo', 'root', 'logo')
            ->add('header', 'logo', 'header');

        $this->getLayoutView();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExtensions()
    {
        $testBlockType = $this->createMock(AbstractType::class);
        $testBlockType->expects($this->any())
            ->method('getName')
            ->willReturn('test');
        $testBlockType->expects($this->any())
            ->method('getParent')
            ->willReturn(BaseType::NAME);

        $headerLayoutUpdate = $this->createMock(LayoutUpdateInterface::class);
        $headerLayoutUpdate->expects($this->once())
            ->method('updateLayout')
            ->willReturnCallback(function (LayoutManipulatorInterface $layoutManipulator) {
                $layoutManipulator->add('test', 'header', 'test');
            });

        $headerBlockTypeExtension = $this->createMock(BlockTypeExtensionInterface::class);
        $headerBlockTypeExtension->expects($this->any())
            ->method('getExtendedType')
            ->willReturn('header');
        $headerBlockTypeExtension->expects($this->once())
            ->method('configureOptions')
            ->willReturnCallback(function (OptionsResolver $resolver) {
                $resolver->setDefaults([
                    'test_option_1' => '',
                    'test_option_2' => ['background' => 'red']
                ]);
            });
        $headerBlockTypeExtension->expects($this->once())
            ->method('buildBlock')
            ->willReturnCallback(function (BlockBuilderInterface $builder, Options $options) {
                if ($options['test_option_1'] === 'move_logo_to_root') {
                    $builder->getLayoutManipulator()->move('logo', 'root');
                }
            });
        $headerBlockTypeExtension->expects($this->once())
            ->method('buildView')
            ->willReturnCallback(function (BlockView $view, BlockInterface $block, Options $options) {
                $view->vars['attr']['block_id'] = $block->getId();
                if ($options['test_option_1'] === 'move_logo_to_root') {
                    $view->vars['attr']['logo_moved'] = true;
                }
                $view->vars['attr']['background'] = $options['test_option_2']['background'];
            });
        $headerBlockTypeExtension->expects($this->once())
            ->method('finishView')
            ->willReturnCallback(function (BlockView $view) {
                if (isset($view['test'])) {
                    $view['test']->vars['processed_by_header_extension'] = true;
                }
            });

        $this->registry->addExtension(
            new AbstractExtensionStub(
                [$testBlockType],
                [$headerBlockTypeExtension],
                [
                    'header' => [$headerLayoutUpdate]
                ],
                [],
                []
            )
        );

        $this->context->resolve();

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header', ['test_option_1' => 'move_logo_to_root'])
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => [
                            'id'   => 'header',
                            'attr' => [
                                'block_id'   => 'header',
                                'logo_moved' => true,
                                'background' => 'red'
                            ]
                        ],
                        'children' => [
                            [ // test
                                'vars' => [
                                    'id'                            => 'test',
                                    'processed_by_header_extension' => true
                                ]
                            ]
                        ]
                    ],
                    [ // logo
                        'vars' => ['id' => 'logo', 'title' => 'test']
                    ]
                ]
            ],
            $view
        );
    }

    /**
     * @dataProvider expressionsProvider
     */
    public function testProcessingExpressionsInBuildView(bool $deferred)
    {
        $this->context = new LayoutContext(
            ['expressions_evaluate' => true, 'expressions_evaluate_deferred' => $deferred, 'title' => 'test title'],
            ['expressions_evaluate', 'expressions_evaluate_deferred', 'title']
        );
        $this->context->resolve();

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', [
                'title' => $this->expressionLanguage->parse('context["title"]', ['context'])
            ]);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test title']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function expressionsProvider(): array
    {
        return [
            ['deferred' => false],
            ['deferred' => true],
        ];
    }

    public function testBuildViewShouldFailWhenUsingNonProcessedExpressions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->context = new LayoutContext(
            ['expressions_evaluate' => false, 'title' => 'test title'],
            ['expressions_evaluate', 'title']
        );
        $this->context->resolve();

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', [
                'title' => $this->expressionLanguage->parse('context["title"]', ['context'])
            ]);

        $this->getLayoutView();
    }

    public function testBuildViewShouldFailWhenUsingDataInExpressionsInDeferredMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->context = new LayoutContext(
            ['expressions_evaluate' => true, 'expressions_evaluate_deferred' => true, 'title' => 'test title'],
            ['expressions_evaluate', 'expressions_evaluate_deferred', 'title']
        );
        $this->context->resolve();

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', [
                'title' => $this->expressionLanguage->parse('data["title"]', ['data'])
            ]);

        $this->getLayoutView();
    }

    public function testResolvingValueBags()
    {
        $valueBag = new OptionValueBag();
        $valueBag->add('one');
        $valueBag->add('two');

        $this->context = new LayoutContext(['expressions_evaluate' => true], ['expressions_evaluate']);
        $this->context->resolve();

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => $valueBag]);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'one two']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testExceptionDuringResolveBlockOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot resolve options for the block "logo". Reason: The required option "title" is missing.'
        );

        $this->context->resolve();
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo_with_required_title');
        $this->getLayoutView();
    }
}
