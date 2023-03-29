<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Abstract class for dashboard type fixtures.
 */
abstract class AbstractDashboardTypeFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName('dashboard_type');

        $configManager = $this->container->get('oro_entity_config.config_manager');
        $config = $configManager->getProvider('enum')->getConfig($className);
        $immutableCodes = $config->get('immutable_codes', false, []);

        $manager->persist($manager->getRepository($className)->createEnumValue(
            $this->getDashboardTypeLabel(),
            count($immutableCodes) + 1,
            false,
            $this->getDashboardTypeIdentifier()
        ));
        $manager->flush();

        $immutableCodes[] = $this->getDashboardTypeIdentifier();
        $config->set('immutable_codes', $immutableCodes);

        $configManager->persist($config);
        $configManager->flush();
    }

    abstract protected function getDashboardTypeIdentifier(): string;
    abstract protected function getDashboardTypeLabel(): string;
}
