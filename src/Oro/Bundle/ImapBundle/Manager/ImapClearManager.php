<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
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
            $imapFolder = $folderRepository->findOneBy(['folder' => $folder]);

            if (!$origin->isActive()) {
                $this->removeFolder($imapFolder);
            } elseif (!$folder->isSyncEnabled()) {
                $this->clearFolder($imapFolder);
                $imapFolder->getFolder()->setSynchronizedAt(null);
            }
        }

        if (!$origin->isActive()) {
            $this->em->remove($origin);
            $this->em->flush();
        }
    }

    /**
     * @param ImapEmailFolder $imapFolder
     */
    public function removeFolder(ImapEmailFolder $imapFolder)
    {
        $this->clearFolder($imapFolder);

        $folder = $imapFolder->getFolder();
        $this->em->remove($imapFolder);
        $this->em->remove($folder);

        $this->em->flush();
    }

    /**
     * @param ImapEmailFolder $imapFolder
     */
    public function clearFolder(ImapEmailFolder $imapFolder)
    {
        $folder = $imapFolder->getFolder();
        $emailUsers = $this->em->getRepository('OroEmailBundle:EmailUser')->findBy(['folder' => $folder]);

        foreach ($emailUsers as $emailUser) {
            $this->em->remove($emailUser);

            $email = $emailUser->getEmail();
            $imapEmail = $this->em->getRepository('OroImapBundle:ImapEmail')->findOneBy([
                'email' => $email,
                'imapFolder' => $imapFolder,
            ]);
            $this->em->remove($imapEmail);
        }

        $this->em->flush();

        // todo: add batch
        foreach ($emailUsers as $emailUser) {
            $email = $emailUser->getEmail();
            if ($email->getEmailUsers()->isEmpty()) {
                $emailRecipients = $email->getRecipients();
                foreach ($emailRecipients as $emailRecipient) {
                    $this->em->remove($emailRecipient);
                }

                $this->em->remove($email);
            }
        }

        $this->em->flush();
    }
}
