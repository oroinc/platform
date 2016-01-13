<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class GoogleSyncConfigCheckbox extends ConfigCheckbox
{
    const NAME = 'oro_config_google_imap_sync_checkbox';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ImapEmailGoogleOauth2Manager
     */
    protected $imapEmailGoogleOauth2Manager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, ImapEmailGoogleOauth2Manager $manager)
    {
        $this->imapEmailGoogleOauth2Manager = $manager;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this->initEvents($builder);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSubmit(FormEvent $formEvent)
    {
        $isGoogleImapSyncEnabled = $formEvent->getData();

        if ($isGoogleImapSyncEnabled) {
            $this->refreshTokens();
        } else {
            $this->setTokensToNull();
        }
    }

    /**
     * @return GoogleSyncConfigCheckbox
     */
    protected function refreshTokens()
    {
        $origins = $this->entityManager
            ->getRepository('OroImapBundle:UserEmailOrigin')
            ->getAllOriginsWithRefreshTokens()
            ->getQuery()
            ->getResult();

        $isFlushNeeded = false;
        /** @var UserEmailOrigin $origin */
        foreach ($origins as $origin) {
            //manager will update token
            $token = $this->imapEmailGoogleOauth2Manager->getAccessTokenWithCheckingExpiration($origin);
            if ($token === null) {
                //if token not updated, not null value must be set
                $origin->setAccessToken('');
                $this->entityManager->persist($origin);
                $isFlushNeeded = true;
            }
        }

        if ($isFlushNeeded) {
            $this->entityManager->flush();
        }

        return $this;
    }

    /**
     * @return GoogleSyncConfigCheckbox
     */
    protected function setTokensToNull()
    {
        $origins = $this->entityManager
            ->getRepository('OroImapBundle:UserEmailOrigin')
            ->getAllOriginsWithAccessTokens()
            ->getQuery()
            ->getResult();

        /** @var UserEmailOrigin $origin */
        foreach ($origins as $origin) {
            $origin->setAccessToken(null);
            $origin->setAccessTokenExpiresAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->entityManager->persist($origin);
        }

        $this->entityManager->flush();

        return $this;
    }
}
