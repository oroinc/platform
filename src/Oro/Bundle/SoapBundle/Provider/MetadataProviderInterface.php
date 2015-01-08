<?php

namespace Oro\Bundle\SoapBundle\Provider;

use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;

interface MetadataProviderInterface
{
    /**
     * @param FormAwareInterface|EntityManagerAwareInterface $object
     *
     * @return array
     */
    public function getMetadataFor($object);
}
