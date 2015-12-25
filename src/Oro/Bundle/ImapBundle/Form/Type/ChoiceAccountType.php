<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            'choices' => ['Select' => 'Select Type', 'Gmail' => 'Gmail', 'Other' => 'Other'],
        ]);

        $this->initEvents($builder);
    }

    /**
     * Update form if accountType is Other
     *
     * @param FormBuilderInterface $builder
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) {
            $accountTypeModel = $formEvent->getData();
            $form = $formEvent->getForm();

            if ($accountTypeModel instanceof AccountTypeModel) {
                if ($accountTypeModel->getAccountType() === 'Other') {
                    $form->add('imapConfiguration', 'oro_imap_configuration', ['mapped' => false]);
                }

                if ($accountTypeModel->getAccountType() === 'Gmail') {
                    $form->add('imapGmailConfiguration', 'oro_imap_configuration_gmail');
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\\Bundle\\ImapBundle\\Form\\Model\\AccountTypeModel',
        ]);
    }
}
