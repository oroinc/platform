<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\EventListener\ApplySyncSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\DecodeFolderSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\GmailOAuthSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\OriginFolderSubscriber;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurationGmailType extends AbstractType
{
    const NAME = 'oro_imap_configuration_gmail';

    /** @var TranslatorInterface */
    protected $translator;

    /** ConfigManager */
    protected $userConfigManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TranslatorInterface    $translator
     * @param ConfigManager          $userConfigManager
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $userConfigManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->translator = $translator;
        $this->userConfigManager = $userConfigManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new DecodeFolderSubscriber());
        $this->addOwnerOrganizationEventListener($builder);
        $this->addNewOriginCreateEventListener($builder);
        $this->addPrepopulateRefreshTokenEventListener($builder);
        $builder->addEventSubscriber(new OriginFolderSubscriber());
        $builder->addEventSubscriber(new ApplySyncSubscriber());

        $builder
            ->add('check', ButtonType::class, [
                'label' => $this->translator->trans('oro.imap.configuration.connect'),
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('accessToken', HiddenType::class)
            ->add('refreshToken', HiddenType::class)
            ->add('accessTokenExpiresAt', HiddenType::class)
            ->add('imapHost', HiddenType::class, [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_HOST
            ])
            ->add('imapPort', HiddenType::class, [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_PORT
            ])
            ->add('user', HiddenType::class, [
                'required' => true,
            ])
            ->add('imapEncryption', HiddenType::class, [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_SSL
            ])
            ->add('clientId', HiddenType::class, [
                'data' => $this->userConfigManager->get('oro_google_integration.client_id')
            ])
            ->add('smtpHost', HiddenType::class, [
                'required' => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_HOST
            ])
            ->add('smtpPort', HiddenType::class, [
                'required' => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_PORT
            ])
            ->add('smtpEncryption', HiddenType::class, [
                'required'    => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_SSL
            ]);

        $builder->get('accessTokenExpiresAt')
            ->addModelTransformer(new CallbackTransformer(
                function ($originalAccessTokenExpiresAt) {
                    if ($originalAccessTokenExpiresAt === null) {
                        return '';
                    }

                    $now = new \DateTime('now', new \DateTimeZone('UTC'));
                    return $originalAccessTokenExpiresAt->format('U') - $now->format('U');
                },
                function ($submittedAccessTokenExpiresAt) {
                    if ($submittedAccessTokenExpiresAt instanceof \DateTime) {
                        return $submittedAccessTokenExpiresAt;
                    }

                    $utcTimeZone = new \DateTimeZone('UTC');
                    $newExpireDate =
                        new \DateTime('+' . (int)$submittedAccessTokenExpiresAt . ' seconds', $utcTimeZone);

                    return $newExpireDate;
                }
            ));

        $builder->addEventSubscriber(new GmailOAuthSubscriber($this->translator));
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
                        $data->setOwner($this->tokenAccessor->getUser());
                    }
                    if ($data->getOrganization() === null) {
                        $organization = $this->tokenAccessor->getOrganization()
                            ? $this->tokenAccessor->getOrganization()
                            : $this->tokenAccessor->getUser()->getOrganization();
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

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addPrepopulateRefreshTokenEventListener(FormBuilderInterface $builder)
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
                    $refreshToken = $event->getForm()->get('refreshToken')->getData();
                    if (empty($data['refreshToken']) && $refreshToken) {
                        // populate refreshToken
                        $data['refreshToken'] = $refreshToken;
                    }
                    $event->setData($data);
                } elseif ($entity instanceof UserEmailOrigin) {
                    $event->getForm()->setData(null);
                }
            },
            4
        );
    }
}
