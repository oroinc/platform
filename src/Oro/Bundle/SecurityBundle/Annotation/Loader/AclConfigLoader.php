<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;

use Oro\Component\Config\Loader\CumulativeConfigLoader;

class AclConfigLoader implements AclAnnotationLoaderInterface
{
    /**
     * Loads ACL annotations from config files
     *
     * @param AclAnnotationStorage $storage
     */
    public function load(AclAnnotationStorage $storage)
    {
        $configLoader = new CumulativeConfigLoader();
        $resources    = $configLoader->load('oro_acl_config');
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
