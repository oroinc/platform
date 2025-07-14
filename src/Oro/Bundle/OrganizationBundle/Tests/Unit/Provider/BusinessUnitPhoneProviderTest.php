<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Provider\BusinessUnitPhoneProvider;
use PHPUnit\Framework\TestCase;

class BusinessUnitPhoneProviderTest extends TestCase
{
    private BusinessUnitPhoneProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new BusinessUnitPhoneProvider();
    }

    public function testGetPhoneNumber(): void
    {
        $entity = new BusinessUnit();

        $this->assertNull(
            $this->provider->getPhoneNumber($entity)
        );

        $entity->setPhone('123-123');
        $this->assertEquals(
            '123-123',
            $this->provider->getPhoneNumber($entity)
        );
    }

    public function testGetPhoneNumbers(): void
    {
        $entity = new BusinessUnit();

        $this->assertSame(
            [],
            $this->provider->getPhoneNumbers($entity)
        );

        $entity->setPhone('123-123');
        $this->assertEquals(
            [
                ['123-123', $entity]
            ],
            $this->provider->getPhoneNumbers($entity)
        );
    }
}
