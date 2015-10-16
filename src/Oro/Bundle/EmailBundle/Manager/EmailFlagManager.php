<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerLoaderSelector;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;

/**
 * Class EmailFlagManager
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailFlagManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EntityManager */
    protected $em;

    /** @var EmailFlagManagerLoaderSelector */
    protected $selectorEmailFlagManager;

    /**
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
            $emailFlagManager->setUnseen($emailUser->getFolders()->first(), $emailUser->getEmail());
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
            $emailFlagManager->setSeen($emailUser->getFolders()->first(), $emailUser->getEmail());
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
        try {
            if ($toSeen) {
                $this->setSeen($entity);
            } else {
                $this->setUnseen($entity);
            }
        } catch (\Exception $ex) {
            $this->logger->notice(
                sprintf('Set email flag failed. EmailUser id: %d. Error: %s.', $entity->getId(), $ex->getMessage()),
                ['exception' => $ex]
            );
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
        $folder = $emailUser->getFolders()->first();
        $origin = $emailUser->getOrigin();
        if (!$origin || !$origin->isActive()) {
            return null;
        }
        $emailFlagManagerLoader = $this->selectorEmailFlagManager->select($origin);

        return $emailFlagManagerLoader->select($folder, $this->em);
    }
}
