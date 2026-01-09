<?php

namespace Oro\Bundle\SecurityBundle\Encoder;

/**
 * Defines the contract for symmetric encryption and decryption operations.
 *
 * Implementations of this interface provide methods to encrypt and decrypt data
 * using symmetric cryptography algorithms. This is typically used for securing
 * sensitive data that needs to be stored or transmitted securely.
 */
interface SymmetricCrypterInterface
{
    /**
     * @param string $data
     *
     * @return string
     */
    public function encryptData($data);

    /**
     * @param string $data
     *
     * @return string
     */
    public function decryptData($data);
}
