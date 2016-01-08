<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurationGmailType extends AbstractType
{
    const NAME = 'oro_imap_configuration_gmail';

    /** @var TranslatorInterface */
    protected $translator;

    /** ConfigManager */
    protected $userConfigManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigManager $userConfigManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $userConfigManager,
        SecurityFacade $securityFacade
    ) {
        $this->translator = $translator;
        $this->userConfigManager = $userConfigManager;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addOwnerOrganizationEventListener($builder);
        $this->addNewOriginCreateEventListener($builder);

        $builder
            ->add('check', 'button', [
                'label' => $this->translator->trans('oro.imap.configuration.connect'),
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('accessToken', 'hidden')
            ->add('accessTokenExpiresAt', 'hidden')
            ->add('googleAuthCode', 'hidden')
            ->add('imapHost', 'hidden', [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_HOST
            ])
            ->add('imapPort', 'hidden', [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_PORT
            ])
            ->add('user', 'hidden', [
                'required' => true,
            ])
            ->add('imapEncryption', 'hidden', [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_SSL
            ])
            ->add('clientId', 'hidden', [
                'data' => $this->userConfigManager->get('oro_google_integration.client_id')
            ])
            ->add('smtpHost', 'hidden', [
                'required' => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_HOST
            ])
            ->add('smtpPort', 'hidden', [
                'required' => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_PORT
            ])
            ->add('smtpEncryption', 'hidden', [
                'required'    => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_SSL
            ]);

        $builder->get('accessTokenExpiresAt')
            ->addModelTransformer(new CallbackTransformer(
                function ($originalAccessTokenExpiresAt) {

                    if ($originalAccessTokenExpiresAt === null) {
                        return '';
                    }

                    return $originalAccessTokenExpiresAt->format('U');
                },
                function ($submittedAccessTokenExpiresAt) {

                    if ($submittedAccessTokenExpiresAt instanceof \DateTime) {
                        return $submittedAccessTokenExpiresAt;
                    }

                    $newExpireDate = new \DateTime();
                    $newExpireDate->setTimestamp($submittedAccessTokenExpiresAt);

                    return $newExpireDate;
                }
            ));

        $this->initEvents($builder);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSubmit(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $emailOrigin = $form->getData();

        if (null === $emailOrigin) {
            $data = $formEvent->getData();

            if (null == $data) {
                return;
            }

            $emailOrigin = new UserEmailOrigin();
            $emailOrigin->setAccessToken($data['accessToken']);
        }

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->updateForm($form, $emailOrigin);
        }
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSetData(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $emailOrigin = $formEvent->getData();

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->updateForm($form, $emailOrigin);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\\Bundle\\ImapBundle\\Entity\\UserEmailOrigin'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormInterface $form
     * @param UserEmailOrigin $emailOrigin
     */
    protected function updateForm(FormInterface $form, UserEmailOrigin $emailOrigin)
    {
        if ($emailOrigin->getAccessToken() && $emailOrigin->getAccessToken() !== '') {
            $form->add('checkFolder', 'button', [
                'label' => $this->translator->trans('oro.email.retrieve_folders.label'),
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('folders', 'oro_email_email_folder_tree', [
                'label' => $this->translator->trans('oro.email.folders.label'),
                'attr' => ['class' => 'folder-tree'],
                'tooltip' => $this->translator->trans('oro.email.folders.tooltip'),
            ]);

            $form->remove('check');
        }
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addOwnerOrganizationEventListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var UserEmailOrigin $data */
                $data = $event->getData();
                if ($data !== null) {
                    if (($data->getOwner() === null) && ($data->getMailbox() === null)) {
                        $data->setOwner($this->securityFacade->getLoggedUser());
                    }
                    if ($data->getOrganization() === null) {
                        $organization = $this->securityFacade->getOrganization()
                            ? $this->securityFacade->getOrganization()
                            : $this->securityFacade->getLoggedUser()->getOrganization();
                        $data->setOrganization($organization);
                    }

                    $event->setData($data);
                }
            }
        );
    }

    /**
     * @param FormBuilderInterface $builder
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addNewOriginCreateEventListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = (array) $event->getData();
                /** @var UserEmailOrigin|null $entity */
                $entity = $event->getForm()->getData();
                $filtered = array_filter(
                    $data,
                    function ($item) {
                        return !empty($item);
                    }
                );
                if (count($filtered) > 0) {
                    if ($entity instanceof UserEmailOrigin
                        && $entity->getImapHost() !== null
                        && array_key_exists('imapHost', $data) && $data['imapHost'] !== null
                        && array_key_exists('user', $data) && $data['user'] !== null
                        && ($entity->getImapHost() !== $data['imapHost']
                            || $entity->getUser() !== $data['user'])
                    ) {
                        $newConfiguration = new UserEmailOrigin();
                        $event->getForm()->setData($newConfiguration);
                    }
                } elseif ($entity instanceof UserEmailOrigin) {
                    $event->getForm()->setData(null);
                }
            },
            3
        );
    }
}
