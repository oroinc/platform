<?php

namespace Oro\Bundle\SecurityBundle\Encoder;

use ass\XmlSecurity\Key\Aes256Cbc as Cipher;

class Mcrypt
{
    /** @var string */
    protected $key;

    /** @var Cipher */
    private $cipher;

    /**
     * @param string $key
     */
    public function __construct($key = '')
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptData($data)
    {
        return base64_encode($this->getCipher()->encryptData($data));
    }

    /**
     * {@inheritdoc}
     */
    public function decryptData($data)
    {
        return  str_replace("\x0", '', trim($this->getCipher()->decryptData(base64_decode((string) $data))));
    }

    /**
     * @return Cipher
     */
    protected function getCipher()
    {
        if (null === $this->cipher) {
            $key = $this->key;
            if ($key !== null && strlen($key) !== 32) {
                // use hash in case when key length is not 32
                $key = md5($key);
            }
            $this->cipher = new Cipher($key);
        }

        return $this->cipher;
    }
}
