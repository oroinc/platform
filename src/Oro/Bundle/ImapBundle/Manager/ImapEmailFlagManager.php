<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;

/**
 * Provides a set of methods to manage flags for IMAP email messages.
 */
class ImapEmailFlagManager implements EmailFlagManagerInterface
{
    public const string FLAG_UNSEEN = 'UNSEEN';
    public const string FLAG_SEEN = '\Seen';

    public function __construct(
        private ImapConnector $connector,
        private EntityManagerInterface $em
    ) {
    }

    #[\Override]
    public function setFlags(EmailFolder $folder, Email $email, array $flags): void
    {
        $repoImapEmail = $this->em->getRepository(ImapEmail::class);
        $uid = $repoImapEmail->getUid($folder->getId(), $email->getId());
        $this->connector->selectFolder($folder->getFullName());
        $this->connector->setFlags($uid, $flags);
    }

    #[\Override]
    public function setUnseen(EmailFolder $folder, Email $email): void
    {
        $this->setFlags($folder, $email, [self::FLAG_UNSEEN]);
    }

    #[\Override]
    public function setSeen(EmailFolder $folder, Email $email): void
    {
        $this->setFlags($folder, $email, [self::FLAG_SEEN]);
    }
}
