<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\EventListener\ApplySyncSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\CleanupSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\DecodeFolderSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\OAuthSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\OriginFolderSubscriber;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstraction for configuration forms depending on OAuth providers
 * for IMAP and SMTP
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractOAuthAwareConfigurationType extends AbstractType
{
    /** @var TranslatorInterface */
    protected $translator;

    /** ConfigManager */
    protected $userConfigManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var RequestStack */
    protected $requestStack;

    /** @var OAuthManagerRegistry */
    protected $oauthManagerRegistry;

    /**
     * @param TranslatorInterface    $translator
     * @param ConfigManager          $userConfigManager
     * @param TokenAccessorInterface $tokenAccessor
     * @param RequestStack           $requestStack
     * @param OAuthManagerRegistry   $oauthManagerRegistry
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $userConfigManager,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        OAuthManagerRegistry $oauthManagerRegistry
    ) {
        $this->translator = $translator;
        $this->userConfigManager = $userConfigManager;
        $this->tokenAccessor = $tokenAccessor;
        $this->requestStack = $requestStack;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        $view->vars['is_partial'] = $request->isXmlHttpRequest()
            && (bool)$request->get('formParentName', false);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new DecodeFolderSubscriber());
        $builder->addEventSubscriber(new OAuthSubscriber($this->translator, $this->oauthManagerRegistry));
        $builder->addEventSubscriber(new CleanupSubscriber());
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
            ->add('accountType', HiddenType::class, [
                'required'    => false
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
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserEmailOrigin::class
        ]);
    }

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
                            ?? $this->tokenAccessor->getUser()->getOrganization();
                        $data->setOrganization($organization);
                    }

                    $event->setData($data);
                }
            }
        );
    }

    /**
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
                        && array_key_exists('accountType', $data) && $data['accountType'] !== null
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

    protected function addPrepopulateRefreshTokenEventListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = (array) $event->getData();
                $data['accountType'] = $this->getAccountType();
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

    /**
     * Returns account type for UserEmailOrigin entity
     */
    abstract protected function getAccountType(): string;
}
