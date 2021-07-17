<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrganizationRepositoryTest extends WebTestCase
{
    /** @var OrganizationRepository */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();

        $this->repository = self::getContainer()->get('doctrine')
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class);
    }

    public function testGetOrganizationIds(): void
    {
        $organization = self::getContainer()->get('doctrine')
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class)
            ->getFirst();

        $this->assertFalse(null === $organization);

        $result = $this->repository->getOrganizationIds();
        $this->assertCount(1, $result);
        $this->assertEquals([$organization->getId()], $result);

        $result = $this->repository->getOrganizationIds([$organization->getId()]);
        $this->assertCount(0, $result);
    }

    public function testGetEnabledOrganizationCount(): void
    {
        $organization = (new Organization())->setName('Acme');

        // Default organizations count.
        $this->assertEquals(1, $this->repository->getEnabledOrganizationCount());

        // Disable second organization and check the count of enabled organizations.
        $organization->setEnabled(false);
        $this->updateOrganization($organization);
        $this->assertEquals(1, $this->repository->getEnabledOrganizationCount());

        // Enable second organization and check the count of enabled organizations.
        $organization->setEnabled(true);
        $this->updateOrganization($organization);
        $this->assertEquals(2, $this->repository->getEnabledOrganizationCount());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateOrganization(Organization $organization): void
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Organization::class);
        $em->persist($organization);
        $em->flush();
    }
}
