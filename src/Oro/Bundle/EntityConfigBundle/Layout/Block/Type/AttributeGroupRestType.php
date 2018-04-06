<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class AttributeGroupRestType extends AbstractContainerType
{
    const NAME = 'attribute_group_rest';

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var AttributeRenderRegistry */
    protected $attributeRenderRegistry;

    /**
     * @param AttributeRenderRegistry $attributeRenderRegistry
     * @param LocalizationHelper           $localizationHelper
     */
    public function __construct(
        AttributeRenderRegistry $attributeRenderRegistry,
        LocalizationHelper $localizationHelper
    ) {
        $this->attributeRenderRegistry = $attributeRenderRegistry;
        $this->localizationHelper = $localizationHelper;
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

        $view->vars['tabsOptions'] = $this->getTabsOptions($options['attribute_family']);
    }

    /**
     * @param AttributeFamily $attributeFamily
     *
     * @return array
     */
    private function getTabsOptions(AttributeFamily $attributeFamily)
    {
        $groups = $this->attributeRenderRegistry->getNotRenderedGroups($attributeFamily);
        $tabListOptions = array_map(
            function (AttributeGroup $group) {
                return [
                    'id' => $group->getCode(),
                    'label' => (string)$this->localizationHelper->getLocalizedValue($group->getLabels())
                ];
            },
            $groups->toArray()
        );

        return array_values($tabListOptions);
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
