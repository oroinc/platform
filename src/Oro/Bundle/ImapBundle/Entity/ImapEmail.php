<?php

namespace Oro\Bundle\ImapBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;

/**
 * IMAP Email
 *
 *
 */
#[ORM\Entity(repositoryClass: ImapEmailRepository::class)]
#[ORM\Table(name: 'oro_email_imap')]
#[ORM\Index(columns: ['uid'], name: 'email_imap_uid_idx')]
class ImapEmail
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'uid', type: Types::INTEGER)]
    protected ?int $uid = null;

    #[ORM\ManyToOne(targetEntity: Email::class)]
    #[ORM\JoinColumn(name: 'email_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Email $email = null;

    #[ORM\ManyToOne(targetEntity: ImapEmailFolder::class)]
    #[ORM\JoinColumn(name: 'imap_folder_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ImapEmailFolder $imapFolder = null;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get email UID
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set email UID
     *
     * @param int $uid
     * @return ImapEmail
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get related email object
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set related email object
     *
     * @param Email $email
     * @return ImapEmail
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param ImapEmailFolder $imapFolder
     */
    public function setImapFolder($imapFolder)
    {
        $this->imapFolder = $imapFolder;
    }

    /**
     * @return ImapEmailFolder
     */
    public function getImapFolder()
    {
        return $this->imapFolder;
    }
}
