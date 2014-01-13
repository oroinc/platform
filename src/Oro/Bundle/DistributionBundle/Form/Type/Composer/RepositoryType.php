<?php

namespace Oro\Bundle\DistributionBundle\Form\Type\Composer;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RepositoryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'choice', ['choices' => ['composer' => 'composer', 'vcs' => 'vcs', 'pear' => 'pear']])
            ->add('url', 'text', ['required' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_composer_repository';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\DistributionBundle\Entity\Composer\Repository',
            ]
        );
    }
}
