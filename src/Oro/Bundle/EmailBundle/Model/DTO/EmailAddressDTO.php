<?php

namespace Oro\Bundle\EmailBundle\Model\DTO;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

/**
 * Representation as an object of an email address
 */
class EmailAddressDTO implements EmailHolderInterface
{
    /** @var string */
    private $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
