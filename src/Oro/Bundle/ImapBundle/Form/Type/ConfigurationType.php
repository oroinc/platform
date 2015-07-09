<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
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

    /** @var EntityManager */
    protected $em;

    /**
     * @param Mcrypt              $encryptor
     * @param SecurityFacade      $securityFacade
     * @param TranslatorInterface $translator
     * @param Registry            $doctrine
     */
    public function __construct(
        Mcrypt $encryptor,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        Registry $doctrine
    ) {
        $this->encryptor = $encryptor;
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
        $this->em = $doctrine->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addPrepopulatePasswordEventListener($builder);
        $this->addOwnerOrganizationEventListener($builder);
        $this->addMergeFoldersListener($builder);
        $this->addPostSubmitFoldersListener($builder);

        $builder
            ->add(
                'host',
                'text',
                array('label' => 'oro.imap.configuration.host.label', 'required' => true)
            )
            ->add(
                'port',
                'number',
                array('label' => 'oro.imap.configuration.port.label', 'required' => true)
            )
            ->add(
                'ssl',
                'choice',
                array(
                    'label'       => 'oro.imap.configuration.ssl.label',
                    'choices'     => array('ssl' => 'SSL', 'tls' => 'TLS'),
                    'empty_data'  => null,
                    'empty_value' => '',
                    'required'    => false
                )
            )
            ->add(
                'user',
                'text',
                array('label' => 'oro.imap.configuration.user.label', 'required' => true)
            )
            ->add(
                'password',
                'password',
                array('label' => 'oro.imap.configuration.password.label', 'required' => true)
            )
            ->add('check_connection', new CheckButtonType())
            ->add('folders', 'oro_email_email_folder_tree', [
                'label' => $this->translator->trans('oro.email.folders.label'),
            ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addMergeFoldersListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            /** @var ImapEmailOrigin $origin */
            $origin = $event->getForm()->getData();
            if ($origin !== null) {
                $data = $event->getData();
                if ($origin->getId() && !$origin->getFolders()->isEmpty() && isset($data['folders'])) {
                    $result = [];
                    $this->expandFolderTree($data['folders'], $result);
                    $this->applySyncEnabled($result, $origin->getFolders());
                    unset($data['folders']);
                    $event->setData($data);
                }
            }
        });
    }

    protected function addPostSubmitFoldersListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var ImapEmailOrigin $origin */
            $origin = $event->getData();
            if ($origin->getId()) {
                foreach ($origin->getFolders() as $folder) {
                    $this->em->refresh($folder);
                }
            }
        });
    }

    /**
     * @param array $submittedFolders
     * @param ArrayCollection|EmailFolder[] $existingFolders
     */
    protected function applySyncEnabled($submittedFolders, $existingFolders)
    {
        $submittedFolders = new ArrayCollection($submittedFolders);
        foreach ($existingFolders as $existingFolder) {
            $this->em->refresh($existingFolder);
            $f = $submittedFolders->filter(function ($item) use ($existingFolder) {
                return $item['fullName'] === $existingFolder->getFullName();
            });
            if (!$f->isEmpty()) {
                $item = $f->first();
                $existingFolder->setSyncEnabled($item['syncEnabled']);
/*                $imapEmailFolder = new ImapEmailFolder();
                $imapEmailFolder->setUidValidity($item['uidValidity']);
                $imapEmailFolder->setFolder($existingFolder);
                $this->em->persist($imapEmailFolder);*/
                $this->em->flush($existingFolder);
                $submittedFolders->removeElement($item);
            }
            if (!$existingFolder->getSubFolders()->isEmpty()) {
                $this->applySyncEnabled($submittedFolders, $existingFolder->getSubFolders());
            }
        }
    }

    /**
     * @param array $folders
     * @param array $result
     */
    protected function expandFolderTree($folders, &$result)
    {
        foreach ($folders as $folder) {
            $result[] = $folder;
            if (isset($folder['subFolders'])) {
                $this->expandFolderTree($folder['subFolders'], $result);
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
                        && ($entity->getHost() != $data['host'] || $entity->getUser() != $data['user'])
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
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\\Bundle\\ImapBundle\\Entity\\ImapEmailOrigin',
                'required'   => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
