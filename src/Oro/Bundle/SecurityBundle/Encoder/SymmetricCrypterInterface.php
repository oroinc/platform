<?php

namespace Oro\Bundle\SecurityBundle\Encoder;

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
