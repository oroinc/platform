<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Abstract class for dashboard type fixtures.
 */
abstract class AbstractDashboardTypeFixture extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $className = EnumOption::class;
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $config = $configManager->getProvider('enum')->getConfig(Dashboard::class, 'dashboard_type');
        $immutableCodes = $config->get('immutable_codes', false, []);
        $dashboardTypeOptions = $manager->getRepository($className)->findBy(['enumCode' => 'dashboard_type']);

        $enumOption = $manager->getRepository($className)->createEnumOption(
            'dashboard_type',
            $this->getDashboardTypeIdentifier(),
            $this->getDashboardTypeLabel(),
            count($dashboardTypeOptions) + 1,
        );
        $manager->persist($enumOption);
        $manager->flush();

        $immutableCodes[] = $enumOption->getId();
        $config->set('immutable_codes', $immutableCodes);

        $configManager->persist($config);
        $configManager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadLanguageData::class];
    }

    abstract protected function getDashboardTypeIdentifier(): string;
    abstract protected function getDashboardTypeLabel(): string;
}
