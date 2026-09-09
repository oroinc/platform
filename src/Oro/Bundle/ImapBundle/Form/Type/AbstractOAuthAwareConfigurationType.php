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
use Oro\Bundle\ImapBundle\Manager\OAuthTokenStorage;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
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

    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $userConfigManager,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        OAuthManagerRegistry $oauthManagerRegistry,
        protected OAuthTokenStorage $oauthTokenStorage
    ) {
        $this->translator = $translator;
        $this->userConfigManager = $userConfigManager;
        $this->tokenAccessor = $tokenAccessor;
        $this->requestStack = $requestStack;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        $view->vars['is_partial'] = $request->isXmlHttpRequest()
            && (bool)$request->get('formParentName', false);
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new DecodeFolderSubscriber());
        $builder->addEventSubscriber(new OAuthSubscriber($this->translator, $this->oauthManagerRegistry));
        $builder->addEventSubscriber(new CleanupSubscriber());
        $this->addOwnerOrganizationEventListener($builder);
        $this->addNewOriginCreateEventListener($builder);
        $this->addOAuthTokenEventListener($builder);
        $builder->addEventSubscriber(new OriginFolderSubscriber());
        $builder->addEventSubscriber(new ApplySyncSubscriber());

        $builder
            ->add('check', ButtonType::class, [
                'label' => $this->translator->trans('oro.imap.configuration.connect'),
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('oauthTokenHandle', HiddenType::class, ['mapped' => false])
            ->add('accountType', HiddenType::class, [
                'required'    => false
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserEmailOrigin::class
        ]);
    }

    protected function addOwnerOrganizationEventListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::SUBMIT,
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
                    if (
                        $entity instanceof UserEmailOrigin
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

    protected function addOAuthTokenEventListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = (array) $event->getData();
                $data['accountType'] = $this->getAccountType();
                $event->setData($data);
            },
            4
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = (array) $event->getData();
                $oauthTokenHandle = $data['oauthTokenHandle'] ?? null;
                if (empty($oauthTokenHandle)) {
                    return;
                }

                /** @var UserEmailOrigin|null $entity */
                $entity = $form->getData();
                if (!$entity instanceof UserEmailOrigin) {
                    $entity = new UserEmailOrigin();
                    $form->setData($entity);
                }

                if (!$this->oauthTokenStorage->applyToOrigin($oauthTokenHandle, $this->getAccountType(), $entity)) {
                    $form->addError(new FormError('Invalid or expired OAuth credentials.'));
                }
            },
            2
        );
    }

    /**
     * Returns account type for UserEmailOrigin entity
     */
    abstract protected function getAccountType(): string;
}
