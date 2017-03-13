<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Component\Action\Model\DoctrineTypeMappingExtensionInterface;

class DoctrineTypeMappingProvider
{
    /** @var DoctrineTypeMappingExtensionInterface[] */
    protected $extensions = [];

    /**
     * @param DoctrineTypeMappingExtensionInterface $extension
     */
    public function addExtension(DoctrineTypeMappingExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return array
     */
    public function getDoctrineTypeMappings()
    {
        $types = array_map(
            function (DoctrineTypeMappingExtensionInterface $extension) {
                return $extension->getDoctrineTypeMappings();
            },
            $this->extensions
        );

        return $types ? call_user_func_array('array_merge', $types) : [];
    }
}
