<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\EmailInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Represents an additional user email address.
 */
#[ORM\Entity]
#[ORM\Table('oro_user_email')]
#[ORM\Index(columns: ['email'], name: 'idx_user_email')]
#[Config]
class Email implements EmailInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true], 'dataaudit' => ['auditable' => true]])]
    protected ?string $email = null;

    /**
     * {@inheritdoc}
     */
    public function getEmailField()
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailOwner()
    {
        return $this->getUser();
    }

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
     * Set email
     *
     * @param  string $email
     * @return Email
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set user
     *
     * @param User|null $user
     * @return Email
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getEmail();
    }
}
