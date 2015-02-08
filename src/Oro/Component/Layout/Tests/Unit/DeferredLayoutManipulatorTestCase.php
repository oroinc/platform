<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockFactory;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\ExtensionManager;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\PreloadedExtension;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

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

    /** @var ExtensionManager */
    protected $extensionManager;

    protected function setUp()
    {
        $this->context = new LayoutContext();

        $this->extensionManager = new ExtensionManager();
        $this->extensionManager->addExtension(new CoreExtension());
        $this->extensionManager->addExtension(
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
        $this->layoutManipulator = new DeferredLayoutManipulator(
            $this->rawLayoutBuilder,
            $this->extensionManager
        );
        $this->blockFactory      = new BlockFactory(
            $this->extensionManager,
            $this->layoutManipulator
        );
    }

    /**
     * @param string|null $rootId
     *
     * @return BlockView
     */
    protected function getLayoutView($rootId = null)
    {
        $this->layoutManipulator->applyChanges($this->context);
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();

        return $this->blockFactory->createBlockView($rawLayout, $this->context, $rootId);
    }
}
