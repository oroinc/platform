<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Extension\ExtensionInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\LayoutFactoryInterface;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\Layouts;

/**
 * The base test case that helps testing block types
 */
abstract class BaseBlockTypeTestCase extends LayoutTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var LayoutFactoryInterface */
    protected $layoutFactory;

    protected function setUp(): void
    {
        $this->context = new LayoutContext();

        $layoutFactoryBuilder = Layouts::createLayoutFactoryBuilder();
        $this->initializeLayoutFactoryBuilder($layoutFactoryBuilder);
        foreach ($this->getExtensions() as $extension) {
            $layoutFactoryBuilder->addExtension($extension);
        }
        $this->layoutFactory = $layoutFactoryBuilder->getLayoutFactory();
    }

    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $layoutFactoryBuilder->addRenderer(
            'test',
            $this->createMock(LayoutRendererInterface::class)
        );
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [];
    }

    /**
     * Returns a block type by its name
     */
    protected function getBlockType(string $blockType): BlockTypeInterface
    {
        return $this->layoutFactory->getType($blockType);
    }

    /**
     * Asks the given block type to resolve options
     */
    protected function resolveOptions(string|BlockTypeInterface $blockType, array $options): array
    {
        $blockOptionsResolver = new BlockOptionsResolver($this->layoutFactory->getRegistry());

        return $blockOptionsResolver->resolveOptions($blockType, $options);
    }

    /**
     * Creates a view for the given block type
     */
    protected function getBlockView(string|BlockTypeInterface $blockType, array $options = []): BlockView
    {
        $layoutBuilder = $this->layoutFactory->createLayoutBuilder();
        $layoutBuilder->add(
            ($blockType instanceof BlockTypeInterface ? $blockType->getName() : $blockType) . '_id',
            null,
            $blockType,
            $options
        );

        return $layoutBuilder->getLayout($this->context)->getView();
    }

    /**
     * Creates a builder which can be used to build block hierarchy
     */
    protected function getBlockBuilder(
        string|AbstractType $blockType,
        array $options = [],
        ?string $id = null
    ): TestBlockBuilder {
        return new TestBlockBuilder(
            $this->layoutFactory->createLayoutBuilder(),
            $this->context,
            $id ?: $blockType . '_id',
            $blockType,
            $options
        );
    }
}
