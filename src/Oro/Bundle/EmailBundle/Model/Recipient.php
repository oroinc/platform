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

    /** @var string|null */
    protected $organization;

    /**
     * @param string $email
     * @param string $name
     * @param RecipientEntity|null $entity
     * @param string|null $organization
     */
    public function __construct($email, $name, RecipientEntity $entity = null, $organization = null)
    {
        $this->email = $email;
        $this->name = $name;
        $this->entity = $entity;
        $this->organization = $organization;
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
        if (!$this->organization) {
            return $this->name;
        }

        return sprintf('(%s) %s', $this->organization, $this->name);
    }

    /**
     * @return RecipientEntity|null
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
