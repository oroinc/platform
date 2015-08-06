<?php

namespace Oro\Bundle\EmailBundle\Model;

class Recipient
{
    /** @var string */
    protected $email;

    /** @var string */
    protected $name;

    /**
     * @param string $email
     * @param string $name
     */
    public function __construct($email, $name)
    {
        $this->email = $email;
        $this->name = $name;
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
}
