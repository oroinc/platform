<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * Loads ACL annotations from "Resources/config/oro/acls.yml" files.
 */
class AclConfigLoader implements AclAnnotationLoaderInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/acls.yml';
    private const ROOT_NODE   = 'acls';

    /**
     * {@inheritdoc}
     */
    public function load(AclAnnotationStorage $storage, ResourcesContainerInterface $resourcesContainer): void
    {
        $configLoader = CumulativeConfigLoaderFactory::create('oro_acl_config', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (isset($resource->data[self::ROOT_NODE]) && \is_array($resource->data[self::ROOT_NODE])) {
                foreach ($resource->data[self::ROOT_NODE] as $id => $data) {
                    $data['id'] = $id;
                    $storage->add(new AclAnnotation($data));
                    if (isset($data['bindings'])) {
                        foreach ($data['bindings'] as $binding) {
                            $storage->addBinding(
                                $id,
                                $binding['class'] ?? null,
                                $binding['method'] ?? null
                            );
                        }
                    }
                }
            }
        }
    }
}
