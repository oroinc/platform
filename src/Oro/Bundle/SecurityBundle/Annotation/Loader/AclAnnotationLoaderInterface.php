<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * An interface for ACL annotation loaders.
 */
interface AclAnnotationLoaderInterface
{
    /**
     * Loads ACL annotations.
     */
    public function load(AclAnnotationStorage $storage, ResourcesContainerInterface $resourcesContainer): void;
}
