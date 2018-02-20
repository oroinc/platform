<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ImapClearManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var RegistryInterface */
    protected $registry;

    /** @var OptionalListenerInterface */
    protected $listener;

    /** @var array[] */
    private $deleteBuffer = [];

    /**
     * @param RegistryInterface $registry
     * @param OptionalListenerInterface $listener
     */
    public function __construct(RegistryInterface $registry, OptionalListenerInterface $listener)
    {
        $this->registry = $registry;
        /**
         * @info This listener should be disabled because it does unnecessary work during removing.
         *       It updates date in entities which will be deleted in method clearFolder().
         */
        $this->listener = $listener;
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

        return true;
    }

    /**
     * @param int $originId
     *
     * @return UserEmailOrigin[]
     * @throws \Exception
     */
    protected function getOriginsToClear($originId)
    {
        $originRepository = $this->registry->getRepository(UserEmailOrigin::class);

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
    protected function clearOrigin($origin)
    {
        $this->listener->setEnabled(false);

        $folders = $origin->getFolders();

        foreach ($this->getImapEmailFolders($folders) as $imapFolder) {
            $folder = $imapFolder->getFolder();
            if ($imapFolder && !$origin->isActive()) {
                $this->clearFolder($imapFolder);

                $this->addToDeleteBuffer($imapFolder);
            } elseif ($imapFolder && !$folder->isSyncEnabled()) {
                $this->clearFolder($imapFolder);
                $folder->setSynchronizedAt(null);
            }
        }

        foreach ($folders as $folder) {
            if (!$origin->isActive()) {
                $this->addToDeleteBuffer($folder);
            }
        }

        if (!$origin->isActive()) {
            $this->addToDeleteBuffer($origin);
        }

        $this->cleanUp();

        $this->listener->setEnabled(true);
    }

    /**
     * @param ImapEmailFolder $imapFolder
     */
    protected function clearFolder($imapFolder)
    {
        $folder = $imapFolder->getFolder();

        /** @var EmailUserRepository $repository */
        $repository = $this->registry->getRepository(EmailUser::class);
        $emailUserIterator = new BufferedQueryResultIterator($repository->getEmailUserByFolder($folder));

        /** @var EmailUser $item */
        foreach ($emailUserIterator as $emailUser) {
            /** @var EmailUser $emailUser */
            $emailUser->removeFolder($folder);
            $email = $emailUser->getEmail();

            $imapEmailsIterator = $this->getImapEmails($email, $imapFolder);
            foreach ($imapEmailsIterator as $imapEmail) {
                $this->addToDeleteBuffer($imapEmail);
            }

            if ($emailUser->getFolders()->isEmpty()) {
                $this->addToDeleteBuffer($emailUser);
            }
        }
    }

    /**
     * @return array
     */
    protected function entitiesToClear()
    {
        return [
            ImapEmail::class,
            EmailUser::class,
            ImapEmailFolder::class,
            EmailFolder::class,
            UserEmailOrigin::class,
        ];
    }

    /**
     * Remove entities from buffer
     */
    protected function cleanUp()
    {
        foreach ($this->entitiesToClear() as $className) {
            if (!array_key_exists($className, $this->deleteBuffer)) {
                continue;
            }

            $buffer = $this->deleteBuffer[$className];

            /** @var EntityManager $entityManager */
            $entityManager = $this->registry->getManagerForClass($className);
            foreach ($buffer as $item) {
                $entityManager->remove($item);
                $this->logger->info(sprintf('%s with ID %s removed', $className, $item->getId()));
            }
            $entityManager->flush($buffer);
        }

        $this->deleteBuffer = [];
    }

    /**
     * @param Collection $folders
     *
     * @return ImapEmailFolder[]
     */
    private function getImapEmailFolders(Collection $folders)
    {
        $repository = $this->registry->getRepository(ImapEmailFolder::class);

        $qb = $repository->createQueryBuilder('ief');
        $qb->innerJoin('ief.folder', 'ef');
        $qb->where($qb->expr()->in('ief.folder', ':folders'));
        $qb->setParameter('folders', $folders);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Email $email
     * @param ImapEmailFolder $imapFolder
     *
     * @return BufferedQueryResultIterator
     */
    private function getImapEmails(Email $email, ImapEmailFolder $imapFolder)
    {
        $repository = $this->registry->getRepository(ImapEmail::class);

        $qb = $repository->createQueryBuilder('ie');
        $qb->where($qb->expr()->andX(
            $qb->expr()->eq('ie.email', ':email'),
            $qb->expr()->eq('ie.imapFolder', ':imapFolder')
        ));

        $qb->setParameters([
            'email' => $email,
            'imapFolder' => $imapFolder,
        ]);

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param ImapEmail|EmailUser|ImapEmailFolder|EmailFolder|UserEmailOrigin $entity
     */
    private function addToDeleteBuffer($entity)
    {
        foreach ($this->entitiesToClear() as $entityClass) {
            if (is_a($entity, $entityClass)) {
                $this->deleteBuffer[$entityClass][] = $entity;
            }
        }
    }
}
