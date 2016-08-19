<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

class ImapEmailRemoveManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EntityManager */
    protected $em;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    /**
     * Remove emails which was removed in remote account
     *
     * @param ImapEmailManager $manager
     * @param array $imapFolders
     */
    public function removeRemotelyRemovedEmails($imapFolder, $folder, $manager)
    {
        $this->em->transactional(function () use ($imapFolder, $folder, $manager) {
            $existingUids = $manager->getEmailUIDs();

            $staleImapEmailsQb = $this
                ->em
                ->getRepository('OroImapBundle:ImapEmail')
                ->createQueryBuilder('ie');
            $staleImapEmailsQb
                ->andWhere($staleImapEmailsQb->expr()->eq('ie.imapFolder', ':imap_folder'))
                ->setParameter('imap_folder', $imapFolder);

            if ($existingUids) {
                $staleImapEmailsQb
                    ->andWhere($staleImapEmailsQb->expr()->notIn('ie.uid', ':uids'))
                    ->setParameter('uids', $existingUids);
            }

            $staleImapEmails = (new BufferedQueryResultIterator($staleImapEmailsQb))
                ->setPageCallback(function () {
                    $this->em->flush();
                    $clearClass = [
                        'Oro\Bundle\EmailBundle\Entity\Email',
                        'Oro\Bundle\EmailBundle\Entity\EmailUser',
                        'Oro\Bundle\EmailBundle\Entity\EmailRecipient',
                        'Oro\Bundle\ImapBundle\Entity\ImapEmail',
                        'Oro\Bundle\EmailBundle\Entity\EmailBody',
                    ];

                    foreach ($clearClass as $item) {
                        $this->em->clear($item);
                    }
                });

            /* @var $staleImapEmails ImapEmail[] */
            foreach ($staleImapEmails as $imapEmail) {
                $email = $imapEmail->getEmail();
                $email->getEmailUsers()
                    ->forAll(function ($key, EmailUser $emailUser) use ($folder, $imapEmail) {
                        $existsEmails = $this->em->getRepository('OroImapBundle:ImapEmail')
                            ->findBy(['email' => $imapEmail->getEmail()]);

                        $emailUser->removeFolder($folder);
                        // if existing imapEmail is last for current email or is absent
                        // we remove emailUser and after that will remove last imapEmail and email
                        if (count($existsEmails) <= 1 && !$emailUser->getFolders()->count()) {
                            $this->em->remove($emailUser);
                        }
                    });
                $this->em->remove($imapEmail);
            }
        });
    }

    /**
     * Deletes all empty outdated folders
     *
     * @param EmailOrigin $origin
     */
    public function cleanupOutdatedFolders(EmailOrigin $origin)
    {
        $this->logger->info('Removing empty outdated folders ...');

        /** @var ImapEmailFolderRepository $repo */
        $repo        = $this->em->getRepository('OroImapBundle:ImapEmailFolder');
        $imapFolders = $repo->getEmptyOutdatedFoldersByOrigin($origin);
        $folders     = new ArrayCollection();

        /** @var ImapEmailFolder $imapFolder */
        foreach ($imapFolders as $imapFolder) {
            $emailFolder = $imapFolder->getFolder();
            if ($emailFolder->getSubFolders()->count() === 0) {
                $this->logger->info(sprintf('Remove "%s" folder.', $emailFolder->getFullName()));

                if (!$folders->contains($emailFolder)) {
                    $folders->add($emailFolder);
                }

                $this->em->remove($imapFolder);
            }
        }

        foreach ($folders as $folder) {
            $this->em->remove($folder);
        }

        if (count($folders) > 0) {
            $this->em->flush();
            $this->logger->info(sprintf('Removed %d folder(s).', count($folders)));
        }
    }

    /**
     * Removes email from all outdated folders
     *
     * @param ImapEmail[] $imapEmails The list of all related IMAP emails
     */
    public function removeEmailFromOutdatedFolders(array $imapEmails)
    {
        /** @var ImapEmail[] $outdatedImapEmails */
        $outdatedImapEmails = array_filter(
            $imapEmails,
            function (ImapEmail $imapEmail) {
                return $imapEmail->getImapFolder()->getFolder()->isOutdated();
            }
        );
        foreach ($outdatedImapEmails as $imapEmail) {
            $this->removeImapEmailReference($imapEmail);
        }
    }

    /**
     * Removes an email from a folder linked to the given IMAP email object
     *
     * @param ImapEmail $imapEmail
     */
    protected function removeImapEmailReference(ImapEmail $imapEmail)
    {
        $this->logger->info(
            sprintf(
                'Remove "%s" (UID: %d) email from "%s".',
                $imapEmail->getEmail()->getSubject(),
                $imapEmail->getUid(),
                $imapEmail->getImapFolder()->getFolder()->getFullName()
            )
        );

        $emailUser = $imapEmail->getEmail()->getEmailUserByFolder($imapEmail->getImapFolder()->getFolder());
        if ($emailUser != null) {
            $emailUser->removeFolder($imapEmail->getImapFolder()->getFolder());
            if (!$emailUser->getFolders()->count()) {
                $imapEmail->getEmail()->getEmailUsers()->removeElement($emailUser);
            }
        }
        $this->em->remove($imapEmail);
    }
}
