<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Zend\Mail\Storage;

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
    const UNSEEN = 'UNSEEN';

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
     * Set flags for message by EmailFolder and  Email
     *
     * @param EmailFolder $folder
     * @param Email       $email
     * @param array       $flags
     *
     * @return void
     */
    public function setFlags(EmailFolder $folder, Email $email, $flags)
    {
        $uid = $this->getUid($folder->getId(), $email->getId());
        $this->connector->setFlags($uid, $flags);
    }

    /**
     * Set flag UNSEEN for message by EmailFolder and Email
     *
     * @param EmailFolder $folder
     * @param Email $email
     *
     * @return void
     */
    public function setFlagUnseen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [self::UNSEEN]);
    }

    /**
     * Set flag SEEN for message by EmailFolder and Email
     *
     * @param EmailFolder $folder
     * @param Email $email
     *
     * @return void
     */
    public function setFlagSeen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [Storage::FLAG_SEEN]);
    }

    /**
     * @param integer $folder - id of Folder
     * @param integer $email  - id of Email
     * @return integer|false
     */
    protected function getUid($folder, $email)
    {
        $repo = $this->em->getRepository('OroImapBundle:ImapEmail');
        $query = $repo->createQueryBuilder('e')
            ->select('e.uid')
            ->innerJoin('e.imapFolder', 'if')
            ->where('e.email = ?1 AND if.folder = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, $folder)
            ->getQuery();

        return $query->getSingleScalarResult();
    }
}
