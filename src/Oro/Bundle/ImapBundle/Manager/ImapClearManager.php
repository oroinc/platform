<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailBodyRepository;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailThreadRepository;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository;
use Oro\Bundle\ImapBundle\Entity\Repository\UserEmailOriginRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SearchBundle\Async\Indexer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Purpose of this class is to mass purge emails from certain origin
 */
class ImapClearManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const REINDEX_CHUNK_SIZE = 100;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var Indexer */
    protected $indexer;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param Indexer $indexer
     */
    public function __construct(DoctrineHelper $doctrineHelper, Indexer $indexer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->indexer = $indexer;
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
            $this->logger->info('Nothing to clear');

            return false;
        }

        foreach ($origins as $origin) {
            $this->logger->info(sprintf('Clearing origin: %s, %s', $origin->getId(), $origin));

            $this->clearOrigin($origin);

            $this->logger->info('Origin processed successfully');
        }

        $this->getEntityManager()->flush();
        $this->clearBodiesAndThreads();

        return true;
    }

    /**
     * @param int $originId
     *
     * @return UserEmailOrigin[]
     */
    protected function getOriginsToClear($originId)
    {
        $originRepository = $this->doctrineHelper->getEntityRepositoryForClass(UserEmailOrigin::class);

        if ($originId !== null) {
            /** @var UserEmailOrigin $origin */
            $origin = $originRepository->find($originId);
            if ($origin === null) {
                $this->logger->info(sprintf('Origin with ID %s does not exist', $originId));

                return [];
            }

            $origins = [$origin];
        } else {
            $origins = $originRepository->findAll();
        }

        return $origins;
    }

    /**
     * @param UserEmailOrigin $origin
     */
    protected function clearOrigin(UserEmailOrigin $origin)
    {
        $em = $this->getEntityManager();

        if (!$origin->isActive()) {
            $this->clearEmails($origin);
            $em->remove($origin); // EmailFolders & ImapEmailFolders will be deleted via onDelete=CASCADE
        } else {
            $this->clearEmails($origin, false);

            /** @var ImapEmailFolderRepository $repo */
            $repo = $this->doctrineHelper->getEntityRepositoryForClass(ImapEmailFolder::class);

            $imapFolders = $repo->getFoldersByOrigin($origin, true, EmailFolder::SYNC_ENABLED_FALSE);
            foreach ($imapFolders as $imapFolder) {
                $imapFolder->getFolder()->setSynchronizedAt(null);
            }
        }
    }

    /**
     * Delete emails from specified origin
     * Note that ImapEmails & EmailUsers will be deleted via onDelete=CASCADE
     *
     * @param UserEmailOrigin $origin
     * @param null|bool $syncEnabled
     */
    protected function clearEmails(UserEmailOrigin $origin, $syncEnabled = null)
    {
        /** @var EmailUserRepository $emailUserRepo */
        $emailUserRepo = $this->doctrineHelper->getEntityRepositoryForClass(EmailUser::class);
        $emailUserIdsForReindexation = $emailUserRepo->getIdsFromOrigin($origin);

        /** @var UserEmailOriginRepository $userEmailOriginRepo */
        $userEmailOriginRepo = $this->doctrineHelper->getEntityRepositoryForClass(UserEmailOrigin::class);
        $userEmailOriginRepo->deleteRelatedEmails($origin, $syncEnabled);

        $this->sceduleEmailUsersReindexation($emailUserIdsForReindexation);
    }

    /**
     * Clears orphan EmailBodies & EmailThreads
     * Note that EmailAttachment & EmailAttachmentContent will be deleted via onDelete=CASCADE
     */
    protected function clearBodiesAndThreads()
    {
        /** @var EmailBodyRepository $emailBodyRepo */
        $emailBodyRepo = $this->doctrineHelper->getEntityRepositoryForClass(EmailBody::class);
        $emailBodyRepo->deleteOrphanBodies();

        /** @var EmailThreadRepository $emailThreadRepo */
        $emailThreadRepo = $this->doctrineHelper->getEntityRepositoryForClass(EmailThread::class);
        $emailThreadRepo->deleteOrphanThreads();
    }

    /**
     * @param iterable $emailsUsersIds
     */
    protected function sceduleEmailUsersReindexation($emailsUsersIds)
    {
        $proxies = [];
        foreach ($emailsUsersIds as $id) {
            $proxies[$id] = $this->doctrineHelper->getEntityReference(EmailUser::class, $id);
            if (count($proxies) >= self::REINDEX_CHUNK_SIZE) {
                $this->indexer->save($proxies);
                $proxies = [];
            }
        }

        if ($proxies) {
            $this->indexer->save($proxies);
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(UserEmailOrigin::class);
    }
}
