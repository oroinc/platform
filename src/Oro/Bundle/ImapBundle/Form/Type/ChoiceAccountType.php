<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;

class ChoiceAccountType extends AbstractType
{
    const ACCOUNT_TYPE_GMAIL = 'Gmail';
    const ACCOUNT_TYPE_OTHER = 'Other';
    const ACCOUNT_TYPE_NO_SELECT = 'Select Type';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

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

        if ($options['showDisconnectButton']) {
            $builder->add('disconnect', 'button', [
                'attr' => [
                    'class'=>'btn btn-danger'
                ]
            ]);
        } else {
            $builder->add('accountType', 'choice', [
                'label' => $this->translator->trans('oro.imap.configuration.account_type.label'),
                'choices' => [
                    'Select' => self::ACCOUNT_TYPE_NO_SELECT,
                    'Gmail' => self::ACCOUNT_TYPE_GMAIL,
                    'Other' => self::ACCOUNT_TYPE_OTHER
                ],
            ]);
        }

        $this->initEvents($builder);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSubmit(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $accountTypeModel = $form->getData();

        if (null === $accountTypeModel) {
            $data = $formEvent->getData();

            if (null === $data) {
                return;
            }

            $accountTypeModel =  new AccountTypeModel();
            $accountTypeModel->setAccountType($data['accountType']);
        }

        if ($accountTypeModel instanceof AccountTypeModel) {
            $this->updateForm($form, $accountTypeModel);
        }
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSetData(FormEvent $formEvent)
    {
        $accountTypeModel = $formEvent->getData();
        $form = $formEvent->getForm();

        if (null === $accountTypeModel) {
            return;
        }

        if ($accountTypeModel instanceof AccountTypeModel) {
            $this->updateForm($form, $accountTypeModel);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\\Bundle\\ImapBundle\\Form\\Model\\AccountTypeModel',
            'showDisconnectButton' => false
        ]);
    }

    /**
     * Update form if accountType is changed
     *
     * @param FormBuilderInterface $builder
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormInterface $form
     * @param AccountTypeModel $accountTypeModel
     */
    protected function updateForm(FormInterface $form, AccountTypeModel $accountTypeModel)
    {
        if ($accountTypeModel instanceof AccountTypeModel) {
            if ($accountTypeModel->getAccountType() === self::ACCOUNT_TYPE_OTHER) {
                $form->add('imapConfiguration', 'oro_imap_configuration', ['mapped' => false]);
            }

            if ($accountTypeModel->getAccountType() === self::ACCOUNT_TYPE_GMAIL) {
                $form->add('imapGmailConfiguration', 'oro_imap_configuration_gmail');
            }
        }
    }
}
