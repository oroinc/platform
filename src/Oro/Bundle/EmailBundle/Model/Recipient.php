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

        if ($entity && false !== $start = strrpos($entity->getLabel(), '(')) {
            $this->name .= ' ' . substr($entity->getLabel(), $start);
        }
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        if (!$this->entity || !$this->entity->getOrganization()) {
            return $this->name;
        }

        return sprintf('%s (%s)', $this->name, $this->entity->getOrganization());
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
