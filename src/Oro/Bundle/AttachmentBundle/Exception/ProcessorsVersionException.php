<?php

namespace Oro\Bundle\AttachmentBundle\Exception;

/**
 * Exception for invalid processors version.
 */
class ProcessorsVersionException extends \RuntimeException
{
    /**
     * @var string $binary
     */
    private $binary;

    public function __construct(string $name, string $version, string $binary)
    {
        $this->binary = $binary;
        $message = sprintf('The %s library version "%s" does not meet the needs of the system.', $name, $version);

        parent::__construct($message);
    }

    public function getBinary(): string
    {
        return $this->binary;
    }
}
