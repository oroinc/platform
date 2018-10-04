<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

/**
 * Checks whether the protocol is one of "file://", "http://", "https://", "ftp://", "ftps://" or "ssh2://".
 */
class ProtocolValidator implements ProtocolValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupportedProtocol(string $protocol): bool
    {
        return
            'file' === $protocol
            || 'http' === $protocol
            || 'https' === $protocol
            || 'ftp' === $protocol
            || 'ftps' === $protocol
            || 0 === strpos($protocol, 'ssh2.');
    }
}
