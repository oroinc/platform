<?php

namespace Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\CryptedDataTransformer;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating crypted data transformers.
 *
 * This factory creates instances of {@see CryptedDataTransformer} with the necessary
 * crypter and logger dependencies configured, allowing form fields to encrypt
 * and decrypt sensitive data.
 */
class CryptedDataTransformerFactory implements CryptedDataTransformerFactoryInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    public function __construct(SymmetricCrypterInterface $crypter, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->crypter = $crypter;
    }

    #[\Override]
    public function create()
    {
        $transformer = new CryptedDataTransformer($this->crypter);
        $transformer->setLogger($this->logger);

        return $transformer;
    }
}
