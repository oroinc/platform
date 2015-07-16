<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AutoResponseRuleType extends AbstractType
{
    const NAME = 'oro_email_autoresponserule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entityName', 'hidden', [
                'mapped' => false,
                'data' => 'Oro\Bundle\EmailBundle\Entity\Email',
                'constraints' => [
                    new Assert\IdenticalTo([
                        'value' => 'Oro\Bundle\EmailBundle\Entity\Email'
                    ])
                ]
            ])
            ->add('active')
            ->add('name')
//            ->add('conditions')
//            ->add('template', 'oro_email_template_list')
            ->add('mailbox', 'entity', [
                'class' => 'Oro\Bundle\EmailBundle\Entity\Mailbox',
                'property' => 'label',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\AutoResponseRule',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
