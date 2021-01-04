<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

/**
 * Checks whether the protocol is Gaufrette filesystem abstraction layer protocol.
 */
class GaufretteProtocolValidator implements ProtocolValidatorInterface
{
    /** @var ProtocolValidatorInterface */
    private $innerValidator;

    /** @var string */
    private $gaufretteProtocol;

    /**
     * @param ProtocolValidatorInterface $innerValidator
     * @param string                     $gaufretteProtocol
     */
    public function __construct(ProtocolValidatorInterface $innerValidator, string $gaufretteProtocol)
    {
        $this->innerValidator = $innerValidator;
        $this->gaufretteProtocol = $gaufretteProtocol;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupportedProtocol(string $protocol): bool
    {
        return
            $this->innerValidator->isSupportedProtocol($protocol)
            || ($this->gaufretteProtocol && $this->gaufretteProtocol === $protocol);
    }
}
