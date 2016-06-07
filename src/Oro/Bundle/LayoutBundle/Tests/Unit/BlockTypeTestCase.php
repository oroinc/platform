<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit;

use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

use Oro\Bundle\LayoutBundle\Layout\Block\Type;
use Oro\Bundle\LayoutBundle\Layout\Form\GroupingFormLayoutBuilder;

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
            ->addType(new Type\RootType())
            ->addType(new Type\BodyType())
            ->addType(new Type\FormType())
            ->addType(new Type\FormFieldsType($this->formLayoutBuilder))
            ->addType(new Type\FormStartType())
            ->addType(new Type\FormEndType())
            ->addType(new Type\FormFieldType())
            ->addType(new Type\FieldsetType())
            ->addType(new Type\HeadType())
            ->addType(new Type\TitleType())
            ->addType(new Type\MetaType())
            ->addType(new Type\ScriptType())
            ->addType(new Type\StyleType())
            ->addType(new Type\ExternalResourceType())
            ->addType(new Type\TextType())
            ->addType(new Type\LinkType())
            ->addType(new Type\ButtonType())
            ->addType(new Type\ButtonGroupType())
            ->addType(new Type\ListType())
            ->addType(new Type\OrderedListType())
            ->addType(new Type\ListItemType())
            ->addType(new Type\InputType());
    }
}
