<?php

namespace Oro\Bundle\SecurityBundle\Form\DataTransformer;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Data transformer for security-sensitive data.
 * See BB-9260 and BAP-14664
 */
class CryptedDataTransformer implements DataTransformerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    public function __construct(SymmetricCrypterInterface $crypter)
    {
        $this->crypter = $crypter;
    }

    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        try {
            return $this->crypter->decryptData($value);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error($e->getMessage());
            }

            // Decryption failure, might be caused by invalid/malformed/not encrypted input data.
            return null;
        }
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }

        return $this->crypter->encryptData($value);
    }
}
