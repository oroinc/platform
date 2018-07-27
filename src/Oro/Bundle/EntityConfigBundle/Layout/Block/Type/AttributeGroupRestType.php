<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

/**
 * Block type for showing rest attributes group.
 */
class AttributeGroupRestType extends AbstractContainerType
{
    const NAME = 'attribute_group_rest';

    /** @var AttributeRenderRegistry */
    protected $attributeRenderRegistry;

    /**
     * @param AttributeRenderRegistry $attributeRenderRegistry
     */
    public function __construct(AttributeRenderRegistry $attributeRenderRegistry)
    {
        $this->attributeRenderRegistry = $attributeRenderRegistry;
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
        $groups = $this->attributeRenderRegistry->getNotRenderedGroups($attributeFamily);
        $blockId = $builder->getId();
        foreach ($groups as $group) {
            $layoutManipulator->add(
                $this->getAttributeGroupBlockName($group, $blockType, $blockId),
                $blockId,
                $blockType,
                [
                    'entity' => $entity,
                    'attribute_family' => $attributeFamily,
                    'group' => $group->getCode(),
                    'exclude_from_rest' => $options['exclude_from_rest'],
                    'additional_block_prefixes' => [
                        'attribute_group_rest_attribute_group'
                    ],
                    'attribute_options' => [
                        'additional_block_prefixes' => [
                            'attribute_group_rest_attribute'
                        ],
                    ]
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions($view, $options, ['options']);
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
                'entity',
            ]
        )->setDefaults(
            [
                'options' => [],
                'exclude_from_rest' => false,
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
