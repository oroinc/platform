<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit;

use Oro\Component\Layout\PreloadedExtension;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

use Oro\Bundle\LayoutBundle\Layout\Block\Type;

/**
 * The base test case that helps testing block types
 */
abstract class BlockTypeTestCase extends BaseBlockTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createExtensionManager()
    {
        $extensionManager = parent::createExtensionManager();
        $extensionManager->addExtension(
            new PreloadedExtension(
                [
                    Type\RootType::NAME   => new Type\RootType(),
                    Type\BodyType::NAME   => new Type\BodyType(),
                    Type\HeadType::NAME   => new Type\HeadType(),
                    Type\MetaType::NAME   => new Type\MetaType(),
                    Type\ScriptType::NAME => new Type\ScriptType(),
                    Type\StyleType::NAME  => new Type\StyleType()
                ]
            )
        );

        return $extensionManager;
    }
}
