<?php

namespace Oro\Bundle\SecurityBundle\Attribute\Loader;

use Oro\Bundle\SecurityBundle\Metadata\AclAttributeStorage;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * An interface for ACL attribute loaders.
 */
interface AclAttributeLoaderInterface
{
    /**
     * Loads ACL attributes.
     */
    public function load(AclAttributeStorage $storage, ResourcesContainerInterface $resourcesContainer): void;
}
