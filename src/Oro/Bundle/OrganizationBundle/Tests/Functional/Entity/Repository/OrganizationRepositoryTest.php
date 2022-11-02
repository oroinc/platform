<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrganizationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRepository(): OrganizationRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Organization::class);
    }

    public function testGetOrganizationIds(): void
    {
        $organization = self::getContainer()->get('doctrine')
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class)
            ->getFirst();

        $this->assertFalse(null === $organization);

        $result = $this->getRepository()->getOrganizationIds();
        $this->assertCount(1, $result);
        $this->assertEquals([$organization->getId()], $result);

        $result = $this->getRepository()->getOrganizationIds([$organization->getId()]);
        $this->assertCount(0, $result);
    }

    public function testGetEnabledOrganizationCount(): void
    {
        $organization = (new Organization())->setName('Acme');

        // Default organizations count.
        $this->assertEquals(1, $this->getRepository()->getEnabledOrganizationCount());

        // Disable second organization and check the count of enabled organizations.
        $organization->setEnabled(false);
        $this->updateOrganization($organization);
        $this->assertEquals(1, $this->getRepository()->getEnabledOrganizationCount());

        // Enable second organization and check the count of enabled organizations.
        $organization->setEnabled(true);
        $this->updateOrganization($organization);
        $this->assertEquals(2, $this->getRepository()->getEnabledOrganizationCount());
    }

    private function updateOrganization(Organization $organization): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Organization::class);
        $em->persist($organization);
        $em->flush();
    }
}
