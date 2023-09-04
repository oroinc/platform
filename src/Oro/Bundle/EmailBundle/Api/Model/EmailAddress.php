<?php

namespace Oro\Bundle\EmailBundle\Api\Model;

/**
 * This model is used by create and update API resources to be able to validate submitted email addresses.
 */
class EmailAddress
{
    private ?string $name = null;
    private ?string $email = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
