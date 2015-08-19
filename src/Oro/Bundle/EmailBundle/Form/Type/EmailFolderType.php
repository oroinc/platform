<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailFolderType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\EmailFolder',
            'nesting_level' => 10,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['nesting_level'] > 0) {
            $builder
                ->add('syncEnabled', 'checkbox')
                ->add('fullName', 'hidden')
                ->add('name', 'hidden')
                ->add('type', 'hidden')
                ->add('subFolders', 'collection', [
                    'type' => 'oro_email_email_folder',
                    'allow_add' => true,
                    'options' => [
                        'nesting_level' => --$options['nesting_level'],
                    ],
                ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_email_email_folder';
    }
}
