<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="oro_email_auto_response_rule")
 * @ORM\Entity
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
     *
     * @ Assert\NotBlank
     */
    protected $conditions;

    /**
     * @var EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="EmailTemplate")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id", onDelete="CASCADE")
     * @ Assert\NotBlank
     */
    protected $template;

    /**
     * @var Mailbox
     * 
     * @ORM\ManyToOne(targetEntity="Mailbox")
     * @ORM\JoinColumn(name="mailbox_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    protected $mailbox;

    public function __construct()
    {
        $this->conditions = new ArrayCollection();
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
     * @param AutoResponseRuleCondition[]|Collection $conditions
     *
     * @return $this
     */
    public function setConditions(Collection $conditions)
    {
        foreach ($conditions as $condition) {
            $condition->setRule($this);
        }
        $this->conditions = $conditions;

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
}
