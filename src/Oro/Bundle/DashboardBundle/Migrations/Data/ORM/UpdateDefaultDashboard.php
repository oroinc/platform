<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateDefaultDashboard extends AbstractDashboardFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\DashboardBundle\Migrations\Data\ORM\LoadDashboardData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainDashboard = $this->findAdminDashboard($manager, 'main');

        if ($mainDashboard) {
            $mainDashboard->setIsDefault(true);
            $mainDashboard->setLabel(
                $this->container->get('translator')->trans('oro.dashboard.title.main')
            );

            $manager->flush();
        }
    }
}
