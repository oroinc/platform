<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockFactory;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\Extension\PreloadedExtension;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutRegistry;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\AbstractExtensionStub;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

class BlockFactoryTest extends LayoutTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    /** @var LayoutRegistry */
    protected $registry;

    /** @var BlockFactory */
    protected $blockFactory;

    protected function setUp()
    {
        $this->registry = new LayoutRegistry();
        $this->registry->addExtension(new CoreExtension());
        $this->registry->addExtension(
            new PreloadedExtension(
                [
                    'root'                         => new Type\RootType(),
                    'header'                       => new Type\HeaderType(),
                    'logo'                         => new Type\LogoType(),
                    'test_self_building_container' => new Type\TestSelfBuildingContainerType()
                ]
            )
        );

        $this->context           = new LayoutContext();
        $this->rawLayoutBuilder  = new RawLayoutBuilder();
        $this->layoutManipulator = new DeferredLayoutManipulator($this->registry, $this->rawLayoutBuilder);
        $this->blockFactory      = new BlockFactory($this->registry, $this->layoutManipulator);
    }

    /**
     * @param string|null $rootId
     *
     * @return BlockView
     */
    protected function getLayoutView($rootId = null)
    {
        $this->layoutManipulator->applyChanges($this->context, true);
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();

        return $this->blockFactory->createBlockView($rawLayout, $this->context, $rootId);
    }

    public function testSimpleLayout()
    {
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
        $this->layoutManipulator
            ->add('rootId', null, 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                  => 'rootId',
                    'block_type'          => 'root',
                    'translation_domain'  => 'messages',
                    'unique_block_prefix' => '_rootId',
                    'block_prefixes'      => [
                        BaseType::NAME,
                        ContainerType::NAME,
                        'root',
                        '_rootId'
                    ],
                    'cache_key'           => '_rootId_root'
                ],
                'children' => []
            ],
            $view,
            false
        );
    }

    public function testCoreVariables()
    {
        $this->layoutManipulator
            ->add('rootId', null, 'root')
            ->add('headerId', 'rootId', 'header')
            ->add('logoId', 'headerId', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => [
                    'id'                  => 'rootId',
                    'block_type'          => 'root',
                    'translation_domain'  => 'messages',
                    'unique_block_prefix' => '_rootId',
                    'block_prefixes'      => [
                        BaseType::NAME,
                        ContainerType::NAME,
                        'root',
                        '_rootId'
                    ],
                    'cache_key'           => '_rootId_root'
                ],
                'children' => [
                    [ // header
                        'vars'     => [
                            'id'                  => 'headerId',
                            'block_type'          => 'header',
                            'translation_domain'  => 'messages',
                            'unique_block_prefix' => '_headerId',
                            'block_prefixes'      => [
                                BaseType::NAME,
                                ContainerType::NAME,
                                'header',
                                '_headerId'
                            ],
                            'cache_key'           => '_headerId_header'
                        ],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'                  => 'logoId',
                                    'block_type'          => 'logo',
                                    'translation_domain'  => 'messages',
                                    'unique_block_prefix' => '_logoId',
                                    'block_prefixes'      => [
                                        BaseType::NAME,
                                        'logo',
                                        '_logoId'
                                    ],
                                    'cache_key'           => '_logoId_logo',
                                    'title'               => 'test'
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

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "header" item cannot be added as a child to "logo" item (block type: logo) because only container blocks can have children.
     */
    // @codingStandardsIgnoreEnd
    public function testAddChildToNotContainer()
    {
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
        $testBlockType = $this->getMock('Oro\Component\Layout\Block\Type\AbstractType');
        $testBlockType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test'));
        $testBlockType->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue(BaseType::NAME));

        $headerLayoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $headerLayoutUpdate->expects($this->once())
            ->method('updateLayout')
            ->will(
                $this->returnCallback(
                    function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                        $layoutManipulator->add('test', 'header', 'test');
                    }
                )
            );

        $headerBlockTypeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');
        $headerBlockTypeExtension->expects($this->any())
            ->method('getExtendedType')
            ->will($this->returnValue('header'));
        $headerBlockTypeExtension->expects($this->once())
            ->method('configureOptions')
            ->will(
                $this->returnCallback(
                    function (OptionsResolver $resolver) {
                        $resolver->setDefaults(
                            [
                                'test_option_1' => '',
                                'test_option_2' => '{BG}:red'
                            ]
                        );
                    }
                )
            );
        $headerBlockTypeExtension->expects($this->once())
            ->method('normalizeOptions')
            ->will(
                $this->returnCallback(
                    function (array &$options, ContextInterface $context, DataAccessorInterface $data) {
                        if ($options['test_option_2'] === '{BG}:red') {
                            $options['test_option_2'] = ['background'=> 'red'];
                        }
                    }
                )
            );
        $headerBlockTypeExtension->expects($this->once())
            ->method('buildBlock')
            ->will(
                $this->returnCallback(
                    function (BlockBuilderInterface $builder, array $options) {
                        if ($options['test_option_1'] === 'move_logo_to_root') {
                            $builder->getLayoutManipulator()->move('logo', 'root');
                        }
                    }
                )
            );
        $headerBlockTypeExtension->expects($this->once())
            ->method('buildView')
            ->will(
                $this->returnCallback(
                    function (BlockView $view, BlockInterface $block, array $options) {
                        $view->vars['attr']['block_id'] = $block->getId();
                        if ($options['test_option_1'] === 'move_logo_to_root') {
                            $view->vars['attr']['logo_moved'] = true;
                        }
                        $view->vars['attr']['background'] = $options['test_option_2']['background'];
                    }
                )
            );
        $headerBlockTypeExtension->expects($this->once())
            ->method('finishView')
            ->will(
                $this->returnCallback(
                    function (BlockView $view, BlockInterface $block, array $options) {
                        if (isset($view['test'])) {
                            $view['test']->vars['processed_by_header_extension'] = true;
                        }
                    }
                )
            );

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
}
