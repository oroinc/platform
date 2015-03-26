<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailAttachmentType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_email_attachment';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => 'Oro\Bundle\EmailBundle\Entity\EmailAttachment',
            'intention'          => 'email_attachment',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', 'file');
    }
}
