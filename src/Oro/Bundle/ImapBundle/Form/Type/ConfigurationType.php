<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\SecurityBundle\SecurityFacade;

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
        $this->addPrepopulatePasswordEventListener($builder);
        $this->addOwnerOrganizationEventListener($builder);
        $this->addApplySyncListener($builder);
        $this->addSetOriginToFoldersListener($builder);
        $this->addEnableSMTPImapListener($builder);

        $builder
            ->add('useImap', 'checkbox', [
                'label'    => 'oro.imap.configuration.use_imap.label',
                'attr'     => ['class' => 'imap-config'],
                'required' => false,
                'mapped'   => false
            ])
            ->add('imapHost', 'text', [
                'label'    => 'oro.imap.configuration.imap_host.label',
                'required' => false,
                'attr'     => ['class' => 'critical-field imap-config'],
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('imapPort', 'number', [
                'label'    => 'oro.imap.configuration.imap_port.label',
                'attr'     => ['class' => 'imap-config'],
                'required' => false
            ])
            ->add('useSmtp', 'checkbox', [
                'label'    => 'oro.imap.configuration.use_smtp.label',
                'attr'     => ['class' => 'smtp-config'],
                'required' => false,
                'mapped'   => false
            ])
            ->add('smtpHost', 'text', [
                'label'    => 'oro.imap.configuration.smtp_host.label',
                'attr'     => ['class' => 'critical-field smtp-config'],
                'required' => false,
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('smtpPort', 'number', [
                'label'    => 'oro.imap.configuration.smtp_port.label',
                'attr'     => ['class' => 'smtp-config'],
                'required' => false
            ])
            ->add('ssl', 'choice', [
                'label'       => 'oro.imap.configuration.ssl.label',
                'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
                'empty_data'  => null,
                'empty_value' => '',
                'required'    => false
            ])
            ->add('user', 'text', [
                'label'    => 'oro.imap.configuration.user.label',
                'required' => true,
                'attr'     => ['class' => 'critical-field'],
                'tooltip'  => 'oro.imap.configuration.tooltip',
            ])
            ->add('password', 'password', [
                'label' => 'oro.imap.configuration.password.label', 'required' => true
            ])
            ->add('check_connection', new CheckButtonType(), [
                'label' => $this->translator->trans('oro.imap.configuration.connect_and_retrieve_folders')
            ])
            ->add('folders', 'oro_email_email_folder_tree', [
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
            }
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

                if (!empty($filtered)) {
                    $oldPassword = $event->getForm()->get('password')->getData();
                    if (empty($data['password']) && $oldPassword) {
                        // populate old password
                        $data['password'] = $oldPassword;
                    } else {
                        $data['password'] = $encryptor->encryptData($data['password']);
                    }

                    $event->setData($data);

                    if ($entity instanceof UserEmailOrigin
                        && ($entity->getImapHost() !== $data['imapHost'] || $entity->getUser() !== $data['user'])
                    ) {
                        // in case when critical fields were changed new entity should be created
                        $newConfiguration = new UserEmailOrigin();
                        $event->getForm()->setData($newConfiguration);
                    }
                } elseif ($entity instanceof UserEmailOrigin) {
                    $event->getForm()->setData(null);
                }
            }
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\\Bundle\\ImapBundle\\Entity\\UserEmailOrigin',
            'required'   => false,
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
