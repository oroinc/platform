<?php

namespace Oro\Bundle\SoapBundle\Provider;

use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;

/**
 * Defines the contract for providers that supply metadata for API objects.
 *
 * Implementing providers return metadata arrays that describe properties and capabilities
 * of form-aware and entity-manager-aware objects, supporting extensible metadata retrieval
 * through the chain provider pattern.
 */
interface MetadataProviderInterface
{
    /**
     * @param FormAwareInterface|EntityManagerAwareInterface $object
     *
     * @return array
     */
    public function getMetadataFor($object);
}
