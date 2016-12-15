<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Manager\AttributeManager;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractAttributeType extends AbstractType
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var AttributeManager */
    private $attributeManager;

    /**
     * @param AttributeManager $attributeManager
     */
    public function __construct(AttributeManager $attributeManager)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->attributeManager = $attributeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'attribute' => ''
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        /** @var object $entity */
        $entity = $block->getContext()->get('entity');
        /** @var FieldConfigModel $attribute */
        $attribute = $options['attribute'];
        $view->vars['value'] = $this->propertyAccessor->getValue($entity, $attribute->getFieldName());
        $view->vars['label'] = $this->attributeManager->getAttributeLabel($attribute);
    }
}
