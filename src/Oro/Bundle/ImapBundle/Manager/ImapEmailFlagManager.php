<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;

/**
 * Provides a set of methods to manage flags for IMAP email messages.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImapEmailFlagManager implements EmailFlagManagerInterface
{
    const FLAG_UNSEEN = 'UNSEEN';
    const FLAG_SEEN = '\Seen';

    /** @var ImapConnector */
    protected $connector;

    /** @var EntityManager */
    protected $em;

    public function __construct(ImapConnector $connector, OroEntityManager $em)
    {
        $this->connector = $connector;
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function setFlags(EmailFolder $folder, Email $email, $flags)
    {
        $repoImapEmail = $this->em->getRepository(ImapEmail::class);
        $uid = $repoImapEmail->getUid($folder->getId(), $email->getId());
        $this->connector->selectFolder($folder->getFullName());
        $this->connector->setFlags($uid, $flags);
    }

    /**
     * {@inheritDoc}
     */
    public function setUnseen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [self::FLAG_UNSEEN]);
    }

    /**
     * {@inheritDoc}
     */
    public function setSeen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [self::FLAG_SEEN]);
    }
}
