<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

class BusinessUnitControllerAclTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadUserACLData::class]);
    }

    public function testSearchActionIsAllowedForUserWithBusinessUnitView(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_business_unit_search', ['organizationId' => $this->getOrganizationId()])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    public function testSearchActionIsDeniedForUserWithoutBusinessUnitView(): void
    {
        $this->loginUser(LoadUserACLData::SIMPLE_USER_ROLE_LOCAL);

        $this->client->request(
            'GET',
            $this->getUrl('oro_business_unit_search', ['organizationId' => $this->getOrganizationId()])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    private function getOrganizationId(): int
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        return $organization->getId();
    }
}
