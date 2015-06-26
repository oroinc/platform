<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerLoaderSelector;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;

/**
 * Class EmailFlagManager
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailFlagManager
{
    /** @var EntityManager */
    protected $em;

    /** @var EmailFlagManagerLoaderSelector */
    protected $selectorEmailFlagManager;

    /**
     * Constructor
     *
     * @param EmailFlagManagerLoaderSelector $selectorEmailTagManager
     * @param EntityManager                  $em
     */
    public function __construct(
        EmailFlagManagerLoaderSelector $selectorEmailTagManager,
        EntityManager $em
    ) {
        $this->selectorEmailFlagManager = $selectorEmailTagManager;
        $this->em = $em;
    }

    /**
     * Set flag UNSEEN
     *
     * @param EmailUser $emailUser - EmailUser entity
     *
     * @return void
     */
    public function setUnseen(EmailUser $emailUser)
    {
        $emailFlagManager = $this->selectEmailFlagManager($emailUser);

        if ($emailFlagManager instanceof EmailFlagManagerInterface) {
            $emailFlagManager->setFlagUnseen($emailUser->getFolder(), $emailUser->getEmail());
        }
    }

    /**
     * Set flag SEEN
     *
     * @param EmailUser $emailUser - EmailUser entity
     *
     * @return void
     */
    public function setSeen(EmailUser $emailUser)
    {
        $emailFlagManager = $this->selectEmailFlagManager($emailUser);

        if ($emailFlagManager instanceof EmailFlagManagerInterface) {
            $emailFlagManager->setFlagSeen($emailUser->getFolder(), $emailUser->getEmail());
        }
    }

    /**
     * Set flags SEEN|UNSEEN
     *
     * @param EmailUser $entity - EmailUser entity
     * @param bool      $toSeen - It defines how change status.
     * if $toSeen is true then will set flag SEEN else UNSEEN
     *
     * @return void
     */
    public function changeStatusSeen(EmailUser $entity, $toSeen)
    {
        if ($toSeen) {
            $this->setSeen($entity);
        } else {
            $this->setUnseen($entity);
        }
    }

    /**
     * Select email flag manager by entity EmailUser
     *
     * @param EmailUser $emailUser - EmailUser
     *
     * @return EmailFlagManagerInterface
     */
    protected function selectEmailFlagManager(EmailUser $emailUser)
    {
        $folder = $emailUser->getFolder();
        $origin = $folder->getOrigin();
        $emailFlagManagerLoader = $this->selectorEmailFlagManager->select($origin);

        return $emailFlagManagerLoader->select($folder, $this->em);
    }
}
