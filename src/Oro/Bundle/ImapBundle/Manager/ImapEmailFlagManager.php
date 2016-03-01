<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Class ImapEmailFlagManager
 *
 * @package Oro\Bundle\ImapBundle\Manager
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImapEmailFlagManager implements EmailFlagManagerInterface
{
    const FLAG_UNSEEN = 'UNSEEN';
    const FLAG_SEEN = '\Seen';

    /** @var ImapConnector */
    protected $connector;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param ImapConnector $connector
     * @param OroEntityManager $em
     */
    public function __construct(ImapConnector $connector, OroEntityManager $em)
    {
        $this->connector = $connector;
        $this->em = $em;
    }

    /**
     * (@inherit)
     */
    public function setFlags(EmailFolder $folder, Email $email, $flags)
    {
        $repoImapEmail = $this->em->getRepository('OroImapBundle:ImapEmail');
        $uid = $repoImapEmail->getUid($folder->getId(), $email->getId());
        $this->connector->selectFolder($folder->getFullName());
        $this->connector->setFlags($uid, $flags);
    }

    /**
     * (@inherit)
     */
    public function setUnseen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [self::FLAG_UNSEEN]);
    }

    /**
     * (@inherit)
     */
    public function setSeen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [self::FLAG_SEEN]);
    }
}
