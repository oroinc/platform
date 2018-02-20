<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockFactory;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\Extension\PreloadedExtension;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutRegistry;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class DeferredLayoutManipulatorTestCase extends LayoutTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    /** @var BlockFactory */
    protected $blockFactory;

    /** @var LayoutRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->context = new LayoutContext();

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
        $this->rawLayoutBuilder  = new RawLayoutBuilder();
        $this->layoutManipulator = new DeferredLayoutManipulator($this->registry, $this->rawLayoutBuilder);
        $expressionLanguage      = new ExpressionLanguage();
        $expressionProcessor     = new ExpressionProcessor($expressionLanguage, new ExpressionEncoderRegistry([]));
        $this->blockFactory      = new BlockFactory($this->registry, $this->layoutManipulator, $expressionProcessor);
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
}
