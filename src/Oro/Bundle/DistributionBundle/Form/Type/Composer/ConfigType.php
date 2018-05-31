<?php

namespace Oro\Bundle\DistributionBundle\Form\Type\Composer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oauth', TextType::class, ['label' => 'Github OAuth', 'required' => false])
            ->add(
                'repositories',
                CollectionType::class,
                ['entry_type' => RepositoryType::class, 'allow_add' => true, 'allow_delete' => true]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_composer_config';
    }
}
