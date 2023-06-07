<?php

namespace Oro\Bundle\ImapBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * IMAP Email
 *
 * @ORM\Table(
 *     name="oro_email_imap",
 *     indexes={
 *          @ORM\Index(name="email_imap_uid_idx", columns={"uid"})
 *      }
 * )
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository")
 */
class ImapEmail
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="uid", type="integer")
     */
    protected $uid;

    /**
     * @var Email
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\Email")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $email;

    /**
     * @var ImapEmailFolder
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ImapBundle\Entity\ImapEmailFolder")
     * @ORM\JoinColumn(name="imap_folder_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $imapFolder;

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
