<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

class EmbedFormType extends AbstractType
{
    const NAME = 'embed_form';
    const FIELD_SEPARATOR = '_';

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'form_name' => 'form',
            'instance_name' => '',
        ]);
        $resolver->setDefined([
            'form',
            'form_action',
            'form_route_name',
            'form_route_parameters',
            'form_method',
            'form_enctype',
            'form_data',
            'form_prefix',
            'form_field_prefix',
            'form_group_prefix',
            'render_rest',
            'preferred_fields',
            'groups',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        $this->addBlockType(
            $builder,
            EmbedFormStartType::NAME,
            EmbedFormStartType::SHORT_NAME,
            $options,
            [
                'form',
                'form_name',
                'form_action',
                'form_route_name',
                'form_route_parameters',
                'form_method',
                'form_enctype',
                'additional_block_prefixes',
                'instance_name',
            ]
        );

        $this->addBlockType(
            $builder,
            EmbedFormFieldsType::NAME,
            EmbedFormFieldsType::SHORT_NAME,
            $options,
            [
                'form',
                'form_name',
                'groups',
                'form_prefix',
                'form_field_prefix',
                'form_group_prefix',
                'form_data',
                'preferred_fields',
                'additional_block_prefixes',
                'instance_name',
            ]
        );

        $this->addBlockType(
            $builder,
            EmbedFormEndType::NAME,
            EmbedFormEndType::SHORT_NAME,
            $options,
            [
                'form',
                'form_name',
                'render_rest',
                'additional_block_prefixes',
                'instance_name',
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

    /**
     * @param BlockBuilderInterface $builder
     * @param string                $name
     * @param string                $shortName
     * @param Options               $options
     * @param array                 $passedOptions
     */
    protected function addBlockType(
        BlockBuilderInterface $builder,
        $name,
        $shortName,
        Options $options,
        array $passedOptions
    ) {
        $options = $options->toArray();
        if (isset($options['additional_block_prefixes'])) {
            foreach ($options['additional_block_prefixes'] as &$blockPrefix) {
                $blockPrefix .= self::FIELD_SEPARATOR.$shortName;
            }
            unset($blockPrefix);
        }

        $builder->getLayoutManipulator()->add(
            $builder->getId() . self::FIELD_SEPARATOR . $shortName,
            $builder->getId(),
            $name,
            array_intersect_key($options, array_flip($passedOptions))
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ContainerType::NAME;
    }
}
