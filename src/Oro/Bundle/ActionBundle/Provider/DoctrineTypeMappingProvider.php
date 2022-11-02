<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Component\Action\Model\DoctrineTypeMappingExtensionInterface;

/**
 * Provides Doctrine type mapping.
 */
class DoctrineTypeMappingProvider
{
    /** @var iterable|DoctrineTypeMappingExtensionInterface[] */
    private $extensions;

    /**
     * @param iterable|DoctrineTypeMappingExtensionInterface[] $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @return array
     */
    public function getDoctrineTypeMappings()
    {
        $types = [];
        foreach ($this->extensions as $extension) {
            $types[] = $extension->getDoctrineTypeMappings();
        }
        if ($types) {
            $types = array_merge(...$types);
        }

        return $types;
    }
}
