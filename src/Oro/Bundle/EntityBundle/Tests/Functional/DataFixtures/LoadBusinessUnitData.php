<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadBusinessUnitData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
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
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();

        $businessUnit = new BusinessUnit();
        $businessUnit->setOrganization($organization);
        $businessUnit->setName('TestBusinessUnit');
        $businessUnit->setEmail('test@mail.com');

        $manager->persist($businessUnit);
        $this->setReference('TestBusinessUnit', $businessUnit);
        $manager->flush();
    }
}
