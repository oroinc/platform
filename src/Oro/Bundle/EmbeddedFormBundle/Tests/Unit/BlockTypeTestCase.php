<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit;

use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\GroupingFormLayoutBuilder;

/**
 * The base test case that helps testing block types
 */
abstract class BlockTypeTestCase extends BaseBlockTypeTestCase
{
    /** @var GroupingFormLayoutBuilder */
    protected $formLayoutBuilder;

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->formLayoutBuilder = new GroupingFormLayoutBuilder();
        $this->formLayoutBuilder->addSimpleFormTypes(['choice', 'datetime', 'date', 'time']);

        $layoutFactoryBuilder
            ->addType(new Type\EmbedFormType())
            ->addType(new Type\EmbedFormFieldsType($this->formLayoutBuilder))
            ->addType(new Type\EmbedFormStartType())
            ->addType(new Type\EmbedFormEndType())
            ->addType(new Type\EmbedFormFieldType());
    }
}
