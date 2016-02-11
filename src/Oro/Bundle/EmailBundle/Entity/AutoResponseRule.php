<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="oro_email_auto_response_rule")
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository")
 */
class AutoResponseRule
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $active = true;

    /**
     * @var AutoResponseRuleCondition[]|Collection
     * @ORM\OneToMany(targetEntity="AutoResponseRuleCondition", mappedBy="rule", cascade={"persist"})
     * @ORM\OrderBy({"position"="ASC"})
     */
    protected $conditions;

    /**
     * @var EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="EmailTemplate", cascade={"persist"})
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    protected $template;

    /**
     * @var Mailbox
     *
     * @ORM\ManyToOne(targetEntity="Mailbox", inversedBy="autoResponseRules")
     * @ORM\JoinColumn(name="mailbox_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $mailbox;

    /**
     * @var \Datetime
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    public function __construct()
    {
        $this->conditions = new ArrayCollection();
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
     * @return AutoResponseRuleCondition[]|Collection
     */
    public function getConditions()
    {
        return $this->conditions;
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
     * @param AutoResponseRuleCondition[] $conditions
     *
     * @return $this
     */
    public function addConditions(array $conditions)
    {
        array_map([$this, 'addCondition'], $conditions);

        return $this;
    }

    /**
     * @param AutoResponseRuleCondition $condition
     *
     * @return $this
     */
    public function addCondition(AutoResponseRuleCondition $condition)
    {
        $this->conditions->add($condition);
        $condition->setRule($this);

        return $this;
    }

    /**
     * @param AutoResponseRuleCondition $condition
     *
     * @return $this
     */
    public function removeCondition(AutoResponseRuleCondition $condition)
    {
        $this->conditions->removeElement($condition);

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
    public function __toString()
    {
        return (string)$this->getName();
    }
}
