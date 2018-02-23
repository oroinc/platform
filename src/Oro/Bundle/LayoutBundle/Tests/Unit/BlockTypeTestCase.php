<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit;

use Oro\Bundle\LayoutBundle\Layout\Block\Type;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

/**
 * The base test case that helps testing block types
 */
abstract class BlockTypeTestCase extends BaseBlockTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $layoutFactoryBuilder
            ->addType(new Type\InputType())
            ->addType(new Type\FormType());
    }
}
