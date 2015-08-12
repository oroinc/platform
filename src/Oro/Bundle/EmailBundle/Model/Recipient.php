<?php

namespace Oro\Bundle\EmailBundle\Model;

class Recipient
{
    /** @var string */
    protected $email;

    /** @var string */
    protected $name;

    /** @var RecipientEntity|null */
    protected $entity;

    /**
     * @param string $email
     * @param string $name
     * @param RecipientEntity|null $entity
     * @param string|null $organization
     */
    public function __construct($email, $name, RecipientEntity $entity = null)
    {
        $this->email = $email;
        $this->name = trim($name);
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if ($this->entity && false !== $start = strrpos($this->entity->getLabel(), '(')) {
            return sprintf('%s %s', $this->name, substr($this->entity->getLabel(), $start));
        }

        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        if (!$this->entity || !$this->entity->getOrganization()) {
            return $this->getName();
        }

        return sprintf('%s (%s)', $this->getName(), $this->entity->getOrganization());
    }

    /**
     * @return RecipientEntity|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        if (!$this->entity) {
            return $this->email;
        }

        return sprintf('%s|%s', $this->email, $this->entity->getClass());
    }
}
