<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

/**
 * Checks whether the protocol is Gaufrette filesystem abstraction layer protocol.
 */
class GaufretteProtocolValidator implements ProtocolValidatorInterface
{
    /** @var ProtocolValidatorInterface */
    private $innerValidator;

    /** @var string[] */
    private $gaufretteProtocols;

    /**
     * @param ProtocolValidatorInterface $innerValidator
     * @param string[]                   $gaufretteProtocols
     */
    public function __construct(ProtocolValidatorInterface $innerValidator, array $gaufretteProtocols)
    {
        $this->innerValidator = $innerValidator;
        $this->gaufretteProtocols = array_filter($gaufretteProtocols);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupportedProtocol(string $protocol): bool
    {
        return
            $this->innerValidator->isSupportedProtocol($protocol)
            || \in_array($protocol, $this->gaufretteProtocols, true);
    }
}
