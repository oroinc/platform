<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

class LoadPermissionData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /* @var PermissionConfigurationBuilder $builder */
        $builder = $this->container->get('oro_security.configuration.builder.permission_configuration');
        /* @var PermissionManager $permissionsManager */
        $permissionsManager = $this->container->get('oro_security.acl.permission_manager');

        $config = Yaml::parse(file_get_contents(__DIR__ . '/data/permissions.yml'));
        $processor = new Processor();
        $config = $processor->processConfiguration(new PermissionConfiguration(), $config);
        $permissions = $builder->buildPermissions($config);

        $permissionsManager->processPermissions($permissions);

        foreach ($permissions as $permission) {
            $this->addReference($permission->getName(), $permission);
        }
    }
}
