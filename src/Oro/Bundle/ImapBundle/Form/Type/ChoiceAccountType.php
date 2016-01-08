<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;

class ChoiceAccountType extends AbstractType
{
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
                    'Select' => AccountTypeModel::ACCOUNT_TYPE_NO_SELECT,
                    'Gmail' => AccountTypeModel::ACCOUNT_TYPE_GMAIL,
                    'Other' => AccountTypeModel::ACCOUNT_TYPE_OTHER
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

            $accountTypeModel = $this->createAccountTypeModelFromData($data);
        } else {
            $data = $formEvent->getData();

            if ($data) {
                if (isset($data['userEmailOrigin']['accessTokenExpiresAt'])) {
                    $utcTimeZone = new \DateTimeZone('UTC');
                    $accessTokenExpiresAt = $data['userEmailOrigin']['accessTokenExpiresAt'];
                    $newExpireDate = new \DateTime('+' . $accessTokenExpiresAt . ' seconds', $utcTimeZone);

                    $data['userEmailOrigin']['accessTokenExpiresAt'] = $newExpireDate;
                }
                $accountTypeModel = $this->createAccountTypeModelFromData($data);

                $form->remove('disconnect');
                $form->add('accountType', 'choice', [
                    'label' => $this->translator->trans('oro.imap.configuration.account_type.label'),
                    'choices' => [
                        'Select' => AccountTypeModel::ACCOUNT_TYPE_NO_SELECT,
                        'Gmail' => AccountTypeModel::ACCOUNT_TYPE_GMAIL,
                        'Other' => AccountTypeModel::ACCOUNT_TYPE_OTHER
                    ],
                ]);

                $formEvent->setData($data);
            }
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
            if ($accountTypeModel->getAccountType() === AccountTypeModel::ACCOUNT_TYPE_OTHER) {
                $form->add('userEmailOrigin', 'oro_imap_configuration');
            }

            if ($accountTypeModel->getAccountType() === AccountTypeModel::ACCOUNT_TYPE_GMAIL) {
                $form->add('userEmailOrigin', 'oro_imap_configuration_gmail');
            }
        }
    }

    /**
     * Create object of AccountTypeModel using data of form
     *
     * @param array $data
     *
     * @return AccountTypeModel
     */
    protected function createAccountTypeModelFromData($data)
    {
        $accountTypeModel =  new AccountTypeModel();
        $accountTypeModel->setAccountType($data['accountType']);

        $imapGmailConfiguration = $data['userEmailOrigin'];
        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setImapHost($imapGmailConfiguration['imapHost']);
        $userEmailOrigin->setImapPort($imapGmailConfiguration['imapPort']);
        $userEmailOrigin->setImapEncryption($imapGmailConfiguration['imapEncryption']);

        $userEmailOrigin->setUser($imapGmailConfiguration['user']);

        if (isset($imapGmailConfiguration['accessTokenExpiresAt'])) {
            $newExpireDate = $imapGmailConfiguration['accessTokenExpiresAt'];
            if (!$imapGmailConfiguration['accessTokenExpiresAt'] instanceof \Datetime) {
                $utcTimeZone = new \DateTimeZone('UTC');
                $accessTokenExpiresAt = $imapGmailConfiguration['accessTokenExpiresAt'];
                $newExpireDate = new \DateTime('+' . $accessTokenExpiresAt . ' seconds', $utcTimeZone);
            }

            $userEmailOrigin->setAccessTokenExpiresAt($newExpireDate);
        }

        if (isset($imapGmailConfiguration['googleAuthCode'])) {
            $userEmailOrigin->setGoogleAuthCode($imapGmailConfiguration['googleAuthCode']);
        }

        $accountTypeModel->setUserEmailOrigin($userEmailOrigin);

        return $accountTypeModel;
    }
}
