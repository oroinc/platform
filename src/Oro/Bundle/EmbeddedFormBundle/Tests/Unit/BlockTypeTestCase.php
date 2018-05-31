<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\GroupingFormLayoutBuilder;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

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
        $this->formLayoutBuilder->addSimpleFormTypes([
            ChoiceType::class,
            DateTimeType::class,
            DateType::class,
            TimeType::class
        ]);

        $layoutFactoryBuilder
            ->addType(new Type\EmbedFormType())
            ->addType(new Type\EmbedFormFieldsType($this->formLayoutBuilder))
            ->addType(new Type\EmbedFormStartType())
            ->addType(new Type\EmbedFormEndType())
            ->addType(new Type\EmbedFormFieldType());
    }
}
