<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormEndType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldsType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormStartType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormType;
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
    protected GroupingFormLayoutBuilder $formLayoutBuilder;

    #[\Override]
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
            ->addType(new EmbedFormType())
            ->addType(new EmbedFormFieldsType($this->formLayoutBuilder))
            ->addType(new EmbedFormStartType())
            ->addType(new EmbedFormEndType())
            ->addType(new EmbedFormFieldType());
    }
}
