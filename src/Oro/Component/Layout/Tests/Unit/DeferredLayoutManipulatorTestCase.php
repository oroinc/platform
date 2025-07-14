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
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\HeaderType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\RootType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\TestSelfBuildingContainerType;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class DeferredLayoutManipulatorTestCase extends LayoutTestCase
{
    protected LayoutContext $context;
    protected RawLayoutBuilder $rawLayoutBuilder;
    protected DeferredLayoutManipulator $layoutManipulator;
    protected BlockFactory $blockFactory;
    protected LayoutRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new LayoutContext();
        $this->context->resolve();

        $this->registry = new LayoutRegistry();
        $this->registry->addExtension(new CoreExtension());
        $this->registry->addExtension(
            new PreloadedExtension(
                [
                    'root'                         => new RootType(),
                    'header'                       => new HeaderType(),
                    'logo'                         => new LogoType(),
                    'test_self_building_container' => new TestSelfBuildingContainerType()
                ]
            )
        );
        $this->rawLayoutBuilder = new RawLayoutBuilder();
        $this->layoutManipulator = new DeferredLayoutManipulator($this->registry, $this->rawLayoutBuilder);
        $expressionLanguage = new ExpressionLanguage();
        $expressionProcessor = new ExpressionProcessor($expressionLanguage, new ExpressionEncoderRegistry([]));
        $this->blockFactory = new BlockFactory($this->registry, $this->layoutManipulator, $expressionProcessor);
    }

    protected function getLayoutView(): BlockView
    {
        $this->layoutManipulator->applyChanges($this->context, true);
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();

        return $this->blockFactory->createBlockView($rawLayout, $this->context);
    }
}
