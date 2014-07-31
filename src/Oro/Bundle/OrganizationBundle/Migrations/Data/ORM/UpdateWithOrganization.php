<?php
namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class UpdateWithOrganization extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Update given table with default organization
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     * @param string                                     $tableName
     */
    public function update(ObjectManager $manager, $tableName)
    {
        $manager->getRepository('OroOrganizationBundle:Organization')->updateWithOrganization(
            $tableName,
            $this->getReference('default_organization')->getId()
        );
    }
}
