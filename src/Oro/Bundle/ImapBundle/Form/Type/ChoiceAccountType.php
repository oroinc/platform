<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChoiceAccountType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_imap_choice_account_type';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('accountType', 'choice', [
            'label' => 'Account Type',
            'choices' => ['Gmail' => 'Gmail', 'Other' => 'Other'],
            'mapped' => false
        ]);

        $this->initEvents($builder);
    }

    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $formEvent) {
            $product = $formEvent->getData();
            $form = $formEvent->getForm();
//            $form->add('name', TextType::class);
        });
    }


//    /**
//     * {@inheritdoc}
//     */
//    public function setDefaultOptions(OptionsResolverInterface $resolver)
//    {
//
//    }
}
