<?php

namespace Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Defines the contract for creating crypted data transformers.
 *
 * Implementations of this interface are responsible for creating and configuring
 * data transformer instances that handle encryption and decryption of form field data.
 */
interface CryptedDataTransformerFactoryInterface
{
    /**
     * @return DataTransformerInterface
     */
    public function create();
}
