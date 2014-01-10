<?php

namespace Oro\Bundle\DistributionBundle\Form\Type\Composer;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oauth', 'text', ['label' => 'Github OAuth', 'required' => false])
            ->add(
                'repositories',
                'collection',
                ['type' => 'oro_composer_repository', 'allow_add' => true, 'allow_delete' => true]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_composer_config';
    }
}
