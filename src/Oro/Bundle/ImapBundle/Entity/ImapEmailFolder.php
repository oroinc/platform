<?php

namespace Oro\Bundle\ImapBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository;

/**
 * IMAP Email
 */
#[ORM\Entity(repositoryClass: ImapEmailFolderRepository::class)]
#[ORM\Table(name: 'oro_email_folder_imap')]
class ImapEmailFolder
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: EmailFolder::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'folder_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?EmailFolder $folder = null;

    #[ORM\Column(name: 'uid_validity', type: Types::INTEGER)]
    protected ?int $uidValidity = null;

    #[ORM\Column(name: 'last_uid', type: Types::INTEGER, nullable: true)]
    private ?int $lastUid = null;

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
     * Get related email object
     *
     * @return EmailFolder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set related email object
     *
     * @param EmailFolder $folder
     *
     * @return ImapEmailFolder
     */
    public function setFolder(EmailFolder $folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get email UIDVALIDITY
     *
     * @return int
     */
    public function getUidValidity()
    {
        return $this->uidValidity;
    }

    /**
     * Set email UIDVALIDITY
     *
     * @param int $uidValidity
     *
     * @return ImapEmailFolder
     */
    public function setUidValidity($uidValidity)
    {
        $this->uidValidity = $uidValidity;

        return $this;
    }

    public function getLastUid(): ?int
    {
        return $this->lastUid;
    }

    public function setLastUid(int $lastUid): ImapEmailFolder
    {
        $this->lastUid = $lastUid;

        return $this;
    }
}
