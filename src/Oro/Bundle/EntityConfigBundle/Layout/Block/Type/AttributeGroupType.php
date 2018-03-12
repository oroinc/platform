<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class AttributeGroupType extends AbstractContainerType
{
    const NAME = 'attribute_group';

    /** @var AttributeRenderRegistry */
    protected $attributeRenderRegistry;

    /** @var AttributeBlockTypeMapperInterface */
    protected $blockTypeMapper;

    /** @var AttributeManager */
    protected $attributeManager;

    /** @var array */
    protected $notRenderableAttributeTypes = [];

    /**
     * @param AttributeRenderRegistry      $attributeRenderRegistry
     * @param AttributeManager                  $attributeManager
     * @param AttributeBlockTypeMapperInterface $blockTypeMapper
     */
    public function __construct(
        AttributeRenderRegistry $attributeRenderRegistry,
        AttributeManager $attributeManager,
        AttributeBlockTypeMapperInterface $blockTypeMapper
    ) {
        $this->attributeRenderRegistry = $attributeRenderRegistry;
        $this->attributeManager = $attributeManager;
        $this->blockTypeMapper = $blockTypeMapper;
    }

    /**
     * @param array $notRenderableAttributeTypes
     */
    public function setNotRenderableAttributeTypes(array $notRenderableAttributeTypes)
    {
        $this->notRenderableAttributeTypes = $notRenderableAttributeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $options['attribute_family'];
        $code = $options['group'];
        $entityValue = $options->get('entity', false);
        $attributeGroup = $attributeFamily->getAttributeGroup($code);

        if (is_null($attributeGroup)) {
            return;
        }

        $excludeFromRest = $options['exclude_from_rest'];

        $options['visible'] = $attributeGroup->getIsVisible();

        if ($excludeFromRest) {
            $this->attributeRenderRegistry->setGroupRendered($attributeFamily, $attributeGroup);
        }

        $layoutManipulator = $builder->getLayoutManipulator();
        $attributeGroupBlockId = $builder->getId();
        $attributes = $this->attributeManager->getAttributesByGroup($attributeGroup);
        foreach ($attributes as $attribute) {
            if (in_array($attribute->getType(), $this->notRenderableAttributeTypes, true)) {
                continue;
            }

            if ($this->attributeRenderRegistry->isAttributeRendered($attributeFamily, $attribute->getFieldName())) {
                continue;
            }

            $fieldName = $attribute->getFieldName();
            $blockType = $this->blockTypeMapper->getBlockType($attribute);
            $layoutManipulator->add(
                $this->getAttributeBlockName($fieldName, $blockType, $attributeGroupBlockId),
                $attributeGroupBlockId,
                $blockType,
                array_merge(
                    [
                        'entity' => $entityValue,
                        'fieldName' => $attribute->getFieldName(),
                        'className' => $attribute->getEntity()->getClassName()
                    ],
                    $options['attribute_options']->toArray()
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions($view, $options, ['group']);
    }

    /**
     * @param string $field_name
     * @param string $blockType
     * @param string $attributeGroupBlockId
     *
     * @return string
     */
    private function getAttributeBlockName($field_name, $blockType, $attributeGroupBlockId)
    {
        return sprintf('%s_%s_%s', $attributeGroupBlockId, $blockType, $field_name);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'group',
                'entity',
                'attribute_family'
            ]
        );
        $resolver->setDefaults(
            [
                'exclude_from_rest' => true,
                'attribute_options' => []
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
