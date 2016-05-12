<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Configuration\PermissionListConfiguration;

class LoadPermissionData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /* @var $configuration PermissionListConfiguration */
        $configuration = $this->container->get('oro_security.configuration.permission_list_configuration');
        /* @var $builder PermissionConfigurationBuilder */
        $builder = $this->container->get('oro_security.configuration.builder.permission_configuration');
        /* @var $permissionsManager PermissionManager */
        $permissionsManager = $this->container->get('oro_security.acl.permission_manager');

        $config = Yaml::parse(file_get_contents(__DIR__ . '/config/oro/permissions.yml'));
        $config = $configuration->processConfiguration($config);
        $permissions = $builder->buildPermissions($config);

        $permissionsManager->processPermissions($permissions);

        foreach ($permissions as $permission) {
            $this->addReference($permission->getName(), $permission);
        }
    }
}
