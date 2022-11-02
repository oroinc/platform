<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Defines dynamic set of choices for OAuth-aware
 * user email origin account types
 */
class ChoiceAccountType extends AbstractType
{
    const NAME = 'oro_imap_choice_account_type';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var OAuthManagerRegistry */
    protected $oauthManagerRegistry;

    /**
     * @param TranslatorInterface $translator
     * @param OAuthManagerRegistry $oauthManagerRegistry
     */
    public function __construct(
        TranslatorInterface $translator,
        OAuthManagerRegistry $oauthManagerRegistry
    ) {
        $this->translator = $translator;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('accountType', ChoiceType::class, [
            'label' => $this->translator->trans('oro.imap.configuration.account_type.label'),
            'tooltip'  => 'oro.imap.configuration.tooltip',
            'required' => false,
            'choice_loader' => new CallbackChoiceLoader($this->getAccountTypeChoicesCallback())
        ]);

        $this->initEvents($builder);
    }

    /**
     * Returns choices callable for available account types
     */
    protected function getAccountTypeChoicesCallback(string $additionalType = null): callable
    {
        return function () use ($additionalType) {
            $choices = [
                $this->translator->trans(
                    'oro.imap.configuration.account_type.' . AccountTypeModel::ACCOUNT_TYPE_NO_SELECT
                ) => ''
            ];

            foreach ($this->oauthManagerRegistry->getManagers() as $manager) {
                if ($manager->isOAuthEnabled()) {
                    $choices[$this->translator->trans(
                        'oro.imap.configuration.account_type.' . $manager->getType()
                    )] = $manager->getType();
                }
            }

            if ((null !== $additionalType) && !in_array($additionalType, $choices)) {
                $choices[$this->translator->trans(
                    'oro.imap.configuration.account_type.' . $additionalType
                )] = $additionalType;
            }

            $choices[$this->translator->trans(
                'oro.imap.configuration.account_type.' . AccountTypeModel::ACCOUNT_TYPE_OTHER
            )] = AccountTypeModel::ACCOUNT_TYPE_OTHER;

            return $choices;
        };
    }

    public function preSubmit(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $data = $formEvent->getData();

        if (null === $data) {
            return;
        }
        $accountTypeModel = $this->createAccountTypeModelFromData($data);

        if ($accountTypeModel === null) {
            // reset data for avoiding form extra parameters error
            $formEvent->setData(null);
            if ($form->has('userEmailOrigin')) {
                $originForm = $form->get('userEmailOrigin');
                $form->remove('accountType');
                // Resets account type to remove disabled OAuth provider from rendering
                $form->add('accountType', ChoiceType::class, [
                    'label' => $this->translator->trans('oro.imap.configuration.account_type.label'),
                    'tooltip'  => 'oro.imap.configuration.tooltip',
                    'required' => false,
                    'choice_loader' => new CallbackChoiceLoader(
                        $this->getAccountTypeChoicesCallback()
                    )
                ]);
                $originForm->setData(null);
            }
            return;
        } elseif ($form->getData() && $form->getData()->getAccountType() !== $data['accountType']) {
            //set data here, for renew viewData of the form
            $form->setData($accountTypeModel);
        }

        if ($accountTypeModel instanceof AccountTypeModel) {
            $this->updateForm($form, $accountTypeModel);
        }
    }

    public function preSetData(FormEvent $formEvent)
    {
        $accountTypeModel = $formEvent->getData();
        $form = $formEvent->getForm();

        if ($accountTypeModel instanceof AccountTypeModel) {
            $this->updateChoices($form, $accountTypeModel);
            $this->updateForm($form, $accountTypeModel);
        }
    }

    protected function updateChoices(FormInterface $form, AccountTypeModel $accountTypeModel): void
    {
        $origin = $accountTypeModel->getUserEmailOrigin();
        if ((null !== $origin) && !$this->oauthManagerRegistry->isOauthImapEnabled($origin->getAccountType())) {
            $form->remove('accountType');
            $form->add('accountType', ChoiceType::class, [
                'label' => $this->translator->trans('oro.imap.configuration.account_type.label'),
                'tooltip'  => 'oro.imap.configuration.tooltip',
                'required' => false,
                'choice_loader' => new CallbackChoiceLoader(
                    $this->getAccountTypeChoicesCallback($origin->getAccountType())
                )
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => AccountTypeModel::class]);
    }

    /**
     * Update form if accountType is changed
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    protected function updateForm(FormInterface $form, AccountTypeModel $accountTypeModel)
    {
        if ($accountTypeModel instanceof AccountTypeModel) {
            $userEmailOriginType = $this->solveUserEmailOriginType($accountTypeModel);
            if ($userEmailOriginType) {
                $form->add('userEmailOrigin', $userEmailOriginType);
            }
        }
    }

    protected function solveUserEmailOriginType(AccountTypeModel $accountTypeModel): ?string
    {
        switch (true) {
            case $accountTypeModel->getAccountType() === AccountTypeModel::ACCOUNT_TYPE_OTHER:
                return ConfigurationType::class;
            case (($type = $accountTypeModel->getAccountType()) && $this->oauthManagerRegistry->hasManager($type)):
                return $this
                    ->oauthManagerRegistry
                    ->getManager($accountTypeModel->getAccountType())
                    ->getConnectionFormTypeClass();
            default:
                return null;
        }
    }

    /**
     * Create object of AccountTypeModel using data of form
     *
     * @param array $data
     *
     * @return AccountTypeModel|null
     */
    protected function createAccountTypeModelFromData($data)
    {
        $imapConfiguration = $data['userEmailOrigin'] ?? [];

        if (empty($imapConfiguration['user'])) {
            return null;
        }
        $accountTypeModel =  new AccountTypeModel();
        $accountTypeModel->setAccountType($data['accountType']);

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setImapHost($imapConfiguration['imapHost']);
        $userEmailOrigin->setImapPort($imapConfiguration['imapPort']);
        $userEmailOrigin->setImapEncryption($imapConfiguration['imapEncryption']);
        $userEmailOrigin->setAccountType($imapConfiguration['accountType']);

        $userEmailOrigin->setUser($imapConfiguration['user']);

        if (!empty($imapConfiguration['accessTokenExpiresAt'])) {
            $newExpireDate = $imapConfiguration['accessTokenExpiresAt'];
            if (!$newExpireDate instanceof \Datetime) {
                $utcTimeZone = new \DateTimeZone('UTC');
                $accessTokenExpiresAt = $imapConfiguration['accessTokenExpiresAt'];
                $newExpireDate = new \DateTime('+' . $accessTokenExpiresAt . ' seconds', $utcTimeZone);
            }

            $userEmailOrigin->setAccessTokenExpiresAt($newExpireDate);
        }

        $accountTypeModel->setUserEmailOrigin($userEmailOrigin);

        return $accountTypeModel;
    }
}
