<?php

namespace Oro\Bundle\SecurityBundle\Encoder;

/**
 * Crypter that returns equal encoded string for the same data.
 */
class RepetitiveCrypter implements SymmetricCrypterInterface
{
    /** @var string */
    protected $key;

    /** @var string */
    protected $cryptMethod;

    /**
     * @param string $key
     * @param string $cryptMethod
     */
    public function __construct($key = '', $cryptMethod = 'aes-256-cbc')
    {
        $this->key = $key;
        $this->cryptMethod = $cryptMethod;
    }

    /** {@inheritdoc} */
    public function encryptData($data)
    {
        return base64_encode(
            openssl_encrypt(
                $data,
                $this->cryptMethod,
                $this->key,
                OPENSSL_RAW_DATA,
                $this->getIv()
            )
        );
    }

    /** {@inheritdoc} */
    public function decryptData($data)
    {
        return openssl_decrypt(
            base64_decode($data),
            $this->cryptMethod,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->getIv()
        );
    }

    /**
     * @return string
     */
    protected function getIv()
    {
        $ivLength = openssl_cipher_iv_length($this->cryptMethod);
        $hash = md5($this->key);

        return substr($hash, strlen($hash) - $ivLength);
    }
}
