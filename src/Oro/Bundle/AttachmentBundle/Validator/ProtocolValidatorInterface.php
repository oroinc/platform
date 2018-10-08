<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

/**
 * An interface for classes responsible to make a decision
 * whether it is allowed to download a file by the given protocol.
 * @link http://php.net/manual/en/wrappers.php
 */
interface ProtocolValidatorInterface
{
    /**
     * Checks whether it is allowed to download a file by the given protocol.
     *
     * @param string $protocol
     *
     * @return bool
     */
    public function isSupportedProtocol(string $protocol): bool;
}
