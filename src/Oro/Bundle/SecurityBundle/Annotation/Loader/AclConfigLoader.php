<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\DependencyInjection\OroSecurityExtension;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * Loads ACL anotations from "Resources/config/oro/acls.yml" files.
 */
class AclConfigLoader implements AclAnnotationLoaderInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/acls.yml';

    /**
     * {@inheritdoc}
     */
    public function load(AclAnnotationStorage $storage, ResourcesContainerInterface $resourcesContainer): void
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_acl_config',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        $root = OroSecurityExtension::ACLS_CONFIG_ROOT_NODE;
        foreach ($resources as $resource) {
            if (\array_key_exists($root, $resource->data) && \is_array($resource->data[$root])) {
                foreach ($resource->data[$root] as $id => $data) {
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
