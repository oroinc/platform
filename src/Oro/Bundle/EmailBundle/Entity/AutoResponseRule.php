<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository;

/**
 * AutoResponseRule ORM entity.
 */
#[ORM\Entity(repositoryClass: AutoResponseRuleRepository::class)]
#[ORM\Table(name: 'oro_email_auto_response_rule')]
class AutoResponseRule
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    protected ?string $name = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected ?bool $active = true;

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?EmailTemplate $template = null;

    #[ORM\ManyToOne(targetEntity: Mailbox::class, inversedBy: 'autoResponseRules')]
    #[ORM\JoinColumn(name: 'mailbox_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Mailbox $mailbox = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $definition = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return EmailTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return Mailbox
     */
    public function getMailbox()
    {
        return $this->mailbox;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active = true)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @param EmailTemplate $template
     *
     * @return $this
     */
    public function setTemplate(EmailTemplate $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param Mailbox $mailbox
     *
     * @return $this
     */
    public function setMailbox(Mailbox $mailbox)
    {
        $this->mailbox = $mailbox;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param $createdAt \DateTime
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param string $definition
     *
     * @return $this
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return 'email';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }
}
