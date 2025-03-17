<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerLoaderSelector;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Responsible for setting SEEN/UNSEEN flags for email messages.
 */
class EmailFlagManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EmailFlagManagerLoaderSelector $selectorEmailFlagManager,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Sets UNSEEN flag.
     */
    public function setUnseen(EmailUser $emailUser): void
    {
        $this->selectEmailFlagManager($emailUser)?->setUnseen(
            $emailUser->getFolders()->first(),
            $emailUser->getEmail()
        );
    }

    /**
     * Sets SEEN flag.
     */
    public function setSeen(EmailUser $emailUser): void
    {
        $this->selectEmailFlagManager($emailUser)?->setSeen(
            $emailUser->getFolders()->first(),
            $emailUser->getEmail()
        );
    }

    /**
     * Sets SEEN/UNSEEN flags.
     */
    public function changeStatusSeen(EmailUser $entity, bool $seen): void
    {
        try {
            if ($seen) {
                $this->setSeen($entity);
            } else {
                $this->setUnseen($entity);
            }
        } catch (\Exception $ex) {
            $this->logger->info(
                \sprintf('Set email flag failed. EmailUser id: %d. Error: %s.', $entity->getId(), $ex->getMessage()),
                ['exception' => $ex]
            );
        }
    }

    private function selectEmailFlagManager(EmailUser $emailUser): ?EmailFlagManagerInterface
    {
        $folder = $emailUser->getFolders()->first();
        if (!$folder instanceof EmailFolder) {
            return null;
        }
        $origin = $folder->getOrigin();
        if (!$origin || !$origin->isActive()) {
            return null;
        }

        return $this->selectorEmailFlagManager->select($origin)->select($folder, $this->em);
    }
}
