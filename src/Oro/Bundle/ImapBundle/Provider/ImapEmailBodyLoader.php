<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Exception\SyncWithNotificationAlertException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\UnselectableFolderException;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManagerFactory;

/**
 * This class provides ability to load email body
 */
class ImapEmailBodyLoader implements EmailBodyLoaderInterface
{
    public function __construct(
        private ImapEmailManagerFactory $emailManagerFactory,
        private ConfigManager $configManager
    ) {
    }

    #[\Override]
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof UserEmailOrigin;
    }

    #[\Override]
    public function loadEmailBody(EmailFolder $folder, Email $email, EntityManager $em)
    {
        /** @var UserEmailOrigin $origin */
        $origin = $folder->getOrigin();
        $manager = $this->emailManagerFactory->getImapEmailManager($origin);
        try {
            $manager->selectFolder($folder->getFullName());
        } catch (UnselectableFolderException $e) {
            throw new SyncWithNotificationAlertException(
                EmailSyncNotificationAlert::createForSwitchFolderFail(
                    sprintf('The folder "%s" cannot be selected.', $folder->getFullName()),
                ),
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        $repo = $em->getRepository(ImapEmail::class);
        $query = $repo->createQueryBuilder('e')
            ->select('e.uid')
            ->innerJoin('e.imapFolder', 'if')
            ->where('e.email = ?1 AND if.folder = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, $folder)
            ->getQuery();

        $loadedEmail = $manager->findEmail($query->getSingleScalarResult());
        if (null === $loadedEmail) {
            throw new EmailBodyNotFoundException($email);
        }

        $builder = new EmailBodyBuilder($this->configManager);
        $builder->setEmailBody(
            $loadedEmail->getBody()->getContent(),
            $loadedEmail->getBody()->getBodyIsText()
        );
        foreach ($loadedEmail->getAttachments() as $attachment) {
            $builder->addEmailAttachment(
                $attachment->getFileName(),
                $attachment->getContent(),
                $attachment->getContentType(),
                $attachment->getContentTransferEncoding(),
                $attachment->getContentId(),
                $attachment->getFileSize()
            );
        }

        return $builder->getEmailBody();
    }
}
