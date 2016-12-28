<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

class AttributeGroupRestType extends AbstractContainerType
{
    const NAME = 'attribute_group_rest';

    /** @var AttributeGroupRenderRegistry */
    protected $attributeGroupRenderRegistry;

    /**
     * @param AttributeGroupRenderRegistry $attributeGroupRenderRegistry
     */
    public function __construct(AttributeGroupRenderRegistry $attributeGroupRenderRegistry)
    {
        $this->attributeGroupRenderRegistry = $attributeGroupRenderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $options->get('attribute_family');
        $entity = $options->get('entity', false);
        $blockType = AttributeGroupType::NAME;

        $layoutManipulator = $builder->getLayoutManipulator();

        /** @var AttributeGroup[] $groups */
        $groups = $this->attributeGroupRenderRegistry->getNotRenderedGroups($attributeFamily);
        $blockId = $builder->getId();
        foreach ($groups as $group) {
            $layoutManipulator->add(
                $this->getAttributeGroupBlockName($group, $blockType, $blockId),
                $blockId,
                $blockType,
                [
                    'entity' => $entity,
                    'attribute_family' => $attributeFamily,
                    'group' => $group->getCode()
                ]
            );
        }
    }

    /**
     * @param AttributeGroup $group
     * @param string         $blockType
     * @param string         $blockId
     *
     * @return string
     */
    private function getAttributeGroupBlockName(AttributeGroup $group, $blockType, $blockId)
    {
        return sprintf('%s_%s_%s', $blockId, $blockType, $group->getCode());
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'attribute_family',
                'entity'
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
