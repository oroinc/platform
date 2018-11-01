<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

/**
 * Binds email address to an entity for which language can be determined and thus localized email template can be used
 * to send email notifications.
 */
class EmailAddressWithContext implements EmailHolderInterface
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var object
     */
    private $context;

    /**
     * @param string $email
     * @param null $context
     */
    public function __construct(string $email, $context = null)
    {
        $this->email = $email;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return object
     */
    public function getContext()
    {
        return $this->context;
    }
}
