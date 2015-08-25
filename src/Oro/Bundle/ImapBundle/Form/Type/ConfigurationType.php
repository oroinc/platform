<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
        $this->modifySettingsFields($builder);
        $this->addPrepopulatePasswordEventListener($builder);
        $this->addNewOriginCreateEventListener($builder);
        $this->addOwnerOrganizationEventListener($builder);
        $this->addApplySyncListener($builder);
        $this->addSetOriginToFoldersListener($builder);
        $this->addSetMailboxSyncDateListener($builder);
        $this->addEnableSMTPImapListener($builder);
        $this->finalDataCleaner($builder);

        $builder
            ->add('useImap', 'checkbox', [
                'label'    => 'oro.imap.configuration.use_imap.label',
                'attr'     => ['class' => 'imap-config check-connection'],
                'required' => false,
                'mapped'   => false
            ])
            ->add('imapHost', 'text', [
                'label'    => 'oro.imap.configuration.imap_host.label',
                'required' => false,
                'attr'     => ['class' => 'critical-field imap-config check-connection'],
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('imapPort', 'number', [
                'label'    => 'oro.imap.configuration.imap_port.label',
                'attr'     => ['class' => 'imap-config check-connection'],
                'required' => false
            ])
            ->add('imapEncryption', 'choice', [
                'label'       => 'oro.imap.configuration.imap_encryption.label',
                'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
                'attr'        => ['class' => 'imap-config check-connection'],
                'empty_data'  => null,
                'empty_value' => '',
                'required'    => false
            ])
            ->add('useSmtp', 'checkbox', [
                'label'    => 'oro.imap.configuration.use_smtp.label',
                'attr'     => ['class' => 'smtp-config check-connection'],
                'required' => false,
                'mapped'   => false
            ])
            ->add('smtpHost', 'text', [
                'label'    => 'oro.imap.configuration.smtp_host.label',
                'attr'     => ['class' => 'critical-field smtp-config check-connection'],
                'required' => false,
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('smtpPort', 'number', [
                'label'    => 'oro.imap.configuration.smtp_port.label',
                'attr'     => ['class' => 'smtp-config check-connection'],
                'required' => false
            ])
            ->add('smtpEncryption', 'choice', [
                'label'       => 'oro.imap.configuration.smtp_encryption.label',
                'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
                'attr'        => ['class' => 'smtp-config check-connection'],
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
            $builder->add('check_connection', new CheckButtonType(), [
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
    protected function addSetOriginToFoldersListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data !== null && $data instanceof UserEmailOrigin) {
                    foreach ($data->getFolders() as $folder) {
                        $folder->setOrigin($data);
                    }
                    $event->setData($data);
                }
            }
        );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addSetMailboxSyncDateListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data !== null && $data instanceof UserEmailOrigin && $data->getMailbox()) {
                    $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
                    $data->setSynchronizedAt($currentDate);
                    foreach ($data->getFolders() as $folder) {
                        if ($folder->isSyncEnabled()) {
                            $folder->setSynchronizedAt($currentDate);
                        }
                    }
                    $event->setData($data);
                }
            }
        );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addApplySyncListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if (array_key_exists('folders', $data)) {
                    /** @var UserEmailOrigin $origin */
                    $origin = $form->getData();

                    if ($origin !== null && $origin->getId() !== null) {
                        $this->applySyncEnabled($origin->getRootFolders(), $data['folders']);

                        $form->remove('folders');
                        unset($data['folders']);
                    }
                } else {
                    $form->remove('folders');
                }
                $event->setData($data);
            },
            5
        );
    }

    /**
     * @param ArrayCollection $folders
     * @param array $data
     */
    protected function applySyncEnabled($folders, $data)
    {
        /** @var EmailFolder $folder */
        foreach ($folders as $folder) {
            $f = array_filter($data, function ($item) use ($folder) {
                return $folder->getFullName() === $item['fullName'];
            });

            $matched = reset($f);
            $syncEnabled = array_key_exists('syncEnabled', $matched);
            $folder->setSyncEnabled($syncEnabled);

            if (array_key_exists('subFolders', $matched) && $folder->hasSubFolders()) {
                $this->applySyncEnabled($folder->getSubFolders(), $matched['subFolders']);
            }
        }
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
                    if ($data->getOwner() === null) {
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

                if ($form->has('useImap') && $form->get('useImap')->getData() === true) {
                    $groups[] = 'Imap';
                }
                if ($form->has('useSmtp') && $form->get('useSmtp')->getData() === true) {
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
        return self::NAME;
    }
}
