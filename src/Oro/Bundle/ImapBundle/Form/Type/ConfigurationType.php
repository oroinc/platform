<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
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

        $builder
            ->add(
                'host',
                'text',
                [
                    'label' => 'oro.imap.configuration.host.label',
                    'required' => true,
                    'attr' => [
                        'class' => 'critical-field',
                    ],
                    'tooltip' => 'oro.imap.configuration.tooltip',
                ]
            )
            ->add(
                'port',
                'number',
                ['label' => 'oro.imap.configuration.port.label', 'required' => true]
            )
            ->add(
                'ssl',
                'choice',
                [
                    'label'       => 'oro.imap.configuration.ssl.label',
                    'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
                    'empty_data'  => null,
                    'empty_value' => '',
                    'required'    => false
                ]
            )
            ->add(
                'user',
                'text',
                [
                    'label' => 'oro.imap.configuration.user.label',
                    'required' => true,
                    'attr' => [
                        'class' => 'critical-field',
                    ],
                    'tooltip' => 'oro.imap.configuration.tooltip',
                ]
            )
            ->add(
                'password',
                'password',
                ['label' => 'oro.imap.configuration.password.label', 'required' => true]
            )
            ->add('check_connection', new CheckButtonType(), [
                'label' => $this->translator->trans('oro.imap.configuration.connect_and_retrieve_folders')
            ])
            ->add('folders', 'oro_email_email_folder_tree', [
                'label' => $this->translator->trans('oro.email.folders.label'),
                'attr' => [
                    'class' => 'folder-tree',
                ],
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
                if ($data !== null && $data instanceof ImapEmailOrigin) {
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
                    /** @var ImapEmailOrigin $origin */
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
                /** @var ImapEmailOrigin|null $entity */
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

                    if ($entity instanceof ImapEmailOrigin
                        && ($entity->getHost() !== $data['host'] || $entity->getUser() !== $data['user'])
                    ) {
                        // in case when critical fields were changed new entity should be created
                        $newConfiguration = new ImapEmailOrigin();
                        $event->getForm()->setData($newConfiguration);
                    }
                } elseif ($entity instanceof ImapEmailOrigin) {
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
                /** @var ImapEmailOrigin $data */
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
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\\Bundle\\ImapBundle\\Entity\\ImapEmailOrigin',
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
