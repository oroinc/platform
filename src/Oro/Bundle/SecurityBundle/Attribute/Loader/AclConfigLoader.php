<?php

namespace Oro\Bundle\SecurityBundle\Attribute\Loader;

use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeStorage;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * Loads ACL attributes from "Resources/config/oro/acls.yml" files.
 */
class AclConfigLoader implements AclAttributeLoaderInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/acls.yml';
    private const ROOT_NODE   = 'acls';

    /**
     * {@inheritdoc}
     */
    public function load(AclAttributeStorage $storage, ResourcesContainerInterface $resourcesContainer): void
    {
        $configLoader = CumulativeConfigLoaderFactory::create('oro_acl_config', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (isset($resource->data[self::ROOT_NODE]) && \is_array($resource->data[self::ROOT_NODE])) {
                foreach ($resource->data[self::ROOT_NODE] as $id => $data) {
                    $data['id'] = $id;
                    $storage->add(AclAttribute::fromArray($data));
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
