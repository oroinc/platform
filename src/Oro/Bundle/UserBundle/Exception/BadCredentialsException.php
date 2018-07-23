<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\BadCredentialsException as BaseBadCredentialsException;

/**
 * It will be thrown when user credentials are not match with any user.
 */
class BadCredentialsException extends BaseBadCredentialsException
{
    /** @var string */
    private $messageKey;

    /**
     * @param string $messageKey
     */
    public function setMessageKey(string $messageKey)
    {
        $this->messageKey = $messageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return $this->messageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->getToken(), $this->code, $this->messageKey, $this->file, $this->line]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($string)
    {
        list($token, $this->code, $this->messageKey, $this->file, $this->line) = unserialize($string);

        if ($token) {
            $this->setToken($token);
        }
    }
}
