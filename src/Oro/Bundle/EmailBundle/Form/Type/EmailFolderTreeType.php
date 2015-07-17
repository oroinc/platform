<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailFolderTreeType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'type' => 'oro_email_email_folder',
            'allow_add' => true,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_email_email_folder_tree';
    }
}
