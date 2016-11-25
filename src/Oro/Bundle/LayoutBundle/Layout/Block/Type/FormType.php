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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined([
            'form',
            'form_action',
            'form_method',
            'form_multipart',
            'form_route_name',
            'render_rest',
        ]);

        $resolver->setDefaults([
            'form_route_parameters' => [],
            'instance_name' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        $this->addBlockType($builder, 'form_start', $options, [
            'form',
            'form_action',
            'form_method',
            'form_multipart',
            'form_route_name',
            'form_route_parameters',
            'instance_name',
            'additional_block_prefixes',
        ]);

        $this->addBlockType($builder, 'form_fields', $options, [
            'form',
            'additional_block_prefixes',
            'instance_name',
        ]);

        $this->addBlockType($builder, 'form_end', $options, [
            'form',
            'additional_block_prefixes',
            'instance_name',
            'render_rest',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ContainerType::NAME;
    }

    /**
     * {@inheritdoc}
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
    private function addBlockType(BlockBuilderInterface $builder, $name, Options $options, array $passedOptions)
    {
        $suffix = str_replace(self::NAME, '', $name);

        $options = $options->toArray();
        foreach ($options['additional_block_prefixes'] as &$blockPrefix) {
            $blockPrefix .=  $suffix;
        }
        unset($blockPrefix);

        $id = $builder->getId();
        $options = array_intersect_key($options, array_flip($passedOptions));
        $builder->getLayoutManipulator()->add($id . $suffix, $id, $name, $options);
    }
}
