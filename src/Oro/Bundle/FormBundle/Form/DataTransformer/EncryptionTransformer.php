<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class EncryptionTransformer implements DataTransformerInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    private $encoder;

    /**
     * @param SymmetricCrypterInterface $encoder
     */
    public function __construct(SymmetricCrypterInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $this->encoder->decryptData($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $this->encoder->encryptData($value);
    }
}
