<?php

namespace Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory;

use Symfony\Component\Form\DataTransformerInterface;

interface CryptedDataTransformerFactoryInterface
{
    /**
     * @return DataTransformerInterface
     */
    public function create();
}
