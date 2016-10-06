<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

class FormType extends AbstractType
{
    const NAME = 'form';
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
            'split_to_fields',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {

        $this->addBlockType(
            $builder,
            FormStartType::NAME,
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
            FormFieldsType::NAME,
            $options,
            [
                'form',
                'form_name',
                'groups',
                'form_prefix',
                'form_field_prefix',
                'form_group_prefix',
                'split_to_fields',
                'form_data',
                'preferred_fields',
                'additional_block_prefixes',
                'instance_name',
            ]
        );

        $this->addBlockType(
            $builder,
            FormEndType::NAME,
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
     * @param Options               $options
     * @param array                 $passedOptions
     */
    protected function addBlockType(BlockBuilderInterface $builder, $name, Options $options, array $passedOptions)
    {
        $idRegex = '/' . self::FIELD_SEPARATOR . self::NAME . '$/';
        $idSufix = self::FIELD_SEPARATOR . $name;

        $options = $options->toArray();
        foreach ($options['additional_block_prefixes'] as &$blockPrefix) {
            $blockPrefix = preg_replace($idRegex, '', $blockPrefix) . $idSufix;
        }
        unset($blockPrefix);

        $builder->getLayoutManipulator()->add(
            preg_replace($idRegex, '', $builder->getId()) . $idSufix,
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
