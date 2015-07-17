<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;

/**
 * Class ImapClearManager
 *
 * @package Oro\Bundle\EmailBundle\Manager
 */
class ImapClearManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $originId
     *
     * @return bool
     */
    public function clear($originId)
    {
        $origins = $this->getOriginsToClear($originId);
        if (!$origins) {
            return false;
        }
        foreach ($origins as $origin) {
            $this->logger->notice(sprintf('Clearing origin: %s, %s', $origin->getId(), $origin));

            $this->clearOrigin($origin);

            $this->logger->notice('Origin processed successfully');
        }
        return true;
    }

    /**
     * @param int $originId
     *
     * @return ImapEmailOrigin[]
     * @throws \Exception
     */
    protected function getOriginsToClear($originId)
    {
        $originRepository = $this->em->getRepository('OroImapBundle:ImapEmailOrigin');

        if ($originId !== null) {
            /** @var ImapEmailOrigin $origin */
            $origin = $originRepository->find($originId);
            if ($origin === null) {
                $this->logger->notice(sprintf('Origin with ID %s does not exist', $originId));

                return [];
            }

            $origins = [$origin];
        } else {
            $origins = $originRepository->findAll();
        }

        return $origins;
    }

    /**
     * @param ImapEmailOrigin $origin
     */
    protected function clearOrigin(ImapEmailOrigin $origin)
    {
        $folders = $origin->getFolders();
        $folderRepository = $this->em->getRepository('OroImapBundle:ImapEmailFolder');

        foreach ($folders as $folder) {
            $imapFolder = $this->em->getRepository('OroImapBundle:ImapEmailFolder')
                ->findOneBy(['folder' => $folder]);

            if (!$origin->isActive()) {
                $folderRepository->removeFolder($imapFolder);
            } elseif (!$folder->isSyncEnabled()) {
                $folderRepository->clearFolder($imapFolder);
                $imapFolder->getFolder()->setSynchronizedAt(null);
            }
        }

        if (!$origin->isActive()) {
            $this->em->remove($origin);
            $this->em->flush();
        }
    }
}
