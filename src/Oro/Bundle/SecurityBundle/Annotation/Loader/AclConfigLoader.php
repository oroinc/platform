<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;

class AclConfigLoader implements AclAnnotationLoaderInterface
{
    /**
     * Loads ACL annotations from config files
     *
     * @param AclAnnotationStorage $storage
     */
    public function load(AclAnnotationStorage $storage)
    {
        $resources = CumulativeResourceManager::getInstance()
            ->getLoader('oro_acl_config')
            ->load();
        foreach ($resources as $resource) {
            foreach ($resource->data as $id => $data) {
                $data['id'] = $id;
                $storage->add(new AclAnnotation($data));
                if (isset($data['bindings'])) {
                    foreach ($data['bindings'] as $binding) {
                        $storage->addBinding(
                            $id,
                            isset($binding['class']) ? $binding['class'] : null,
                            isset($binding['method']) ? $binding['method'] : null
                        );
                    }
                }
            }
        }
    }
}
