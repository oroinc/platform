<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gridName', 'hidden', ['required' => false])
            ->add('entityClass', 'hidden', ['required' => false])
            ->add('entityId', 'hidden', ['required' => false])
            ->add(
                'from',
                'oro_email_email_address',
                ['required' => true, 'label' => 'oro.email.from_email_address.label']
            )
            ->add('to', 'oro_email_email_address', ['required' => true, 'multiple' => true])
            ->add('subject', 'text', ['required' => true, 'label' => 'oro.email.subject.label'])
            ->add('body', 'textarea', ['required' => false, 'label' => 'oro.email.email_body.label'])
            ->add('template', 'oro_email_template_list', ['required' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                'intention'          => 'email',
                'csrf_protection'    => true,
                'cascade_validation' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_email';
    }
}
