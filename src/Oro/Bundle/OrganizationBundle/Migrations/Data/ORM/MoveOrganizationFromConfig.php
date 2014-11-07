<?php
/**
 * Created by PhpStorm.
 * User: amishchenko
 * Date: 07.11.14
 * Time: 16:21
 */

namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;


use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MoveOrganizationFromConfig extends AbstractFixture implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param Doctrine\Common\Persistence\ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        $repo = $manager->getRepository('OroOrganizationBundle:Organization');
        /** @var Organization $organization */
        $organization = $repo->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION]);

        if (!$organization)
            return;

        $configManager = $this->container->get('oro_config.global');
        $applicationTitle = $configManager->get('oro_ui.application_title');

        if (!$applicationTitle)
            return;

        $organization->setName($applicationTitle);
        $manager->persist($organization);
        $manager->flush();
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

}
