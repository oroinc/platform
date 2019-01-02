<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrganizationRepositoryTest extends WebTestCase
{
    /** @var OrganizationRepository */
    private $repository;

    protected function setUp()
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
}
