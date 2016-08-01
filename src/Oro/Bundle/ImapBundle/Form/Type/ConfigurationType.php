<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\EventListener\ApplySyncSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\DecodeFolderSubscriber;
use Oro\Bundle\ImapBundle\Form\EventListener\OriginFolderSubscriber;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigurationType extends AbstractType
{
    const NAME = 'oro_imap_configuration';

    /** @var Mcrypt */
    protected $encryptor;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param Mcrypt              $encryptor
     * @param SecurityFacade      $securityFacade
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Mcrypt $encryptor,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator
    ) {
        $this->encryptor = $encryptor;
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new DecodeFolderSubscriber());
        $this->modifySettingsFields($builder);
        $this->addPrepopulatePasswordEventListener($builder);
        $this->addNewOriginCreateEventListener($builder);
        $this->addOwnerOrganizationEventListener($builder);
        $builder->addEventSubscriber(new ApplySyncSubscriber());
        $builder->addEventSubscriber(new OriginFolderSubscriber());
        $this->addEnableSMTPImapListener($builder);
        $this->finalDataCleaner($builder);

        $builder
            ->add('useImap', 'checkbox', [
                'label'    => 'oro.imap.configuration.use_imap.label',
                'attr'     => ['class' => 'imap-config check-connection'],
                'required' => false,
                'mapped'   => false,
                'tooltip'  => 'oro.imap.configuration.use_imap.tooltip'
            ])
            ->add('imapHost', 'text', [
                'label'    => 'oro.imap.configuration.imap_host.label',
                'required' => false,
                'attr'     => ['class' => 'critical-field imap-config check-connection switchable-field'],
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('imapPort', 'number', [
                'label'    => 'oro.imap.configuration.imap_port.label',
                'attr'     => ['class' => 'imap-config check-connection switchable-field'],
                'required' => false
            ])
            ->add('imapEncryption', 'choice', [
                'label'       => 'oro.imap.configuration.imap_encryption.label',
                'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
                'attr'        => ['class' => 'imap-config check-connection switchable-field'],
                'empty_data'  => null,
                'empty_value' => '',
                'required'    => false
            ])
            ->add('useSmtp', 'checkbox', [
                'label'    => 'oro.imap.configuration.use_smtp.label',
                'attr'     => ['class' => 'smtp-config check-connection'],
                'required' => false,
                'mapped'   => false,
                'tooltip'  => 'oro.imap.configuration.use_smtp.tooltip'
            ])
            ->add('smtpHost', 'text', [
                'label'    => 'oro.imap.configuration.smtp_host.label',
                'attr'     => ['class' => 'critical-field smtp-config check-connection switchable-field'],
                'required' => false,
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('smtpPort', 'number', [
                'label'    => 'oro.imap.configuration.smtp_port.label',
                'attr'     => ['class' => 'smtp-config check-connection switchable-field'],
                'required' => false
            ])
            ->add('smtpEncryption', 'choice', [
                'label'       => 'oro.imap.configuration.smtp_encryption.label',
                'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
                'attr'        => ['class' => 'smtp-config check-connection switchable-field'],
                'empty_data'  => null,
                'empty_value' => '',
                'required'    => false
            ])
            ->add('user', 'text', [
                'label'    => 'oro.imap.configuration.user.label',
                'required' => true,
                'attr'     => ['class' => 'critical-field check-connection'],
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('password', 'password', [
                'label' => 'oro.imap.configuration.password.label', 'required' => true,
                'attr' => ['class' => 'check-connection']
            ]);
        if ($options['add_check_button']) {
            $builder->add('check_connection', 'oro_imap_configuration_check', [
                'label' => $this->translator->trans('oro.imap.configuration.connect_and_retrieve_folders')
            ]);
        }
        $builder->add('folders', 'oro_email_email_folder_tree', [
                'label'   => $this->translator->trans('oro.email.folders.label'),
                'attr'    => ['class' => 'folder-tree'],
                'tooltip' => 'If a folder is uncheked, all the data saved in it will be deleted',
            ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addPrepopulatePasswordEventListener(FormBuilderInterface $builder)
    {
        $encryptor = $this->encryptor;
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($encryptor) {
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
                    $oldPassword = $event->getForm()->get('password')->getData();
                    if (empty($data['password']) && $oldPassword) {
                        // populate old password
                        $data['password'] = $oldPassword;
                    } else {
                        $data['password'] = $encryptor->encryptData($data['password']);
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
                        /*
                         * In case when critical fields were changed, configuration should be reset.
                         *  - When imap or smtp was disabled, don't create new configuration
                         *  - If imap or smtp are still enabled create a new one.
                         */
                        if ((!array_key_exists('useImap', $data) || $data['useImap'] === 0)
                            && (!array_key_exists('useSmtp', $data) || $data['useSmtp'] === 0)
                        ) {
                            $newConfiguration = null;
                            $event->setData(null);
                        } else {
                            $newConfiguration = new UserEmailOrigin();
                        }
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
     */
    protected function modifySettingsFields(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = (array)$event->getData();
                $entity = $event->getForm()->getData();

                if ($entity instanceof UserEmailOrigin) {
                    /*
                     * If useImap is disabled unset imap related data and set imap host to empty string.
                     * Empty string as host will cause origin to be recreated if necessary.
                     * Old origin will be disabled and later removed in cron job.
                     */
                    if (!array_key_exists('useImap', $data) || $data['useImap'] === 0) {
                        unset($data['imapHost'], $data['imapPort'], $data['imapEncryption']);
                        $data['imapHost'] = '';
                    }
                    /*
                     * If smtp is disabled, unset smtp related data.
                     */
                    if (!array_key_exists('useSmtp', $data) || $data['useSmtp'] === 0) {
                        unset($data['smtpHost'], $data['smtpPort'], $data['smtpEncryption']);
                    }
                    $event->setData($data);
                }
            },
            6
        );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function finalDataCleaner(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = (array)$event->getData();
                $filtered = array_filter(
                    $data,
                    function ($item) {
                        return !empty($item);
                    }
                );

                if (!count($filtered)) {
                    $event->getForm()->remove('useImap');
                    $event->getForm()->remove('useSmtp');
                    $event->getForm()->setData(null);
                }
            },
            1
        );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    public function addEnableSMTPImapListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $formEvent) {
                /** @var UserEmailOrigin $data */
                $data = $formEvent->getData();
                if ($data !== null) {
                    $form = $formEvent->getForm();
                    if ($data->getImapHost() !== null) {
                        $form->get('useImap')->setData(true);
                    }
                    if ($data->getSmtpHost() !== null) {
                        $form->get('useSmtp')->setData(true);
                    }
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => 'Oro\\Bundle\\ImapBundle\\Entity\\UserEmailOrigin',
            'required'          => false,
            'add_check_button'  => true,
            'validation_groups' => function (FormInterface $form) {
                $groups = [];

                $isSubmitted = $form->isSubmitted() === true;
                if (($form->has('useImap') && $form->get('useImap')->getData() === true) || !$isSubmitted) {
                    $groups[] = 'Imap';
                }
                if (($form->has('useSmtp') && $form->get('useSmtp')->getData() === true) || !$isSubmitted) {
                    $groups[] = 'Smtp';
                }

                return $groups;
            },
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
}
