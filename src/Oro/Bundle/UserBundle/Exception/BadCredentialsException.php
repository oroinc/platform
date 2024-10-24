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

    public function setMessageKey(string $messageKey)
    {
        $this->messageKey = $messageKey;
    }

    #[\Override]
    public function getMessageKey(): string
    {
        return $this->messageKey ?? '';
    }

    #[\Override]
    public function __serialize(): array
    {
        return [$this->messageKey, parent::__serialize()];
    }

    #[\Override]
    public function __unserialize(array $data): void
    {
        $this->setMessageKey($data[0]);
        parent::__unserialize($data[1]);

        unset($this->serialized);
    }
}
