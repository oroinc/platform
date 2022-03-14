<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailAddressVisibility;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class EmailAddressVisibilityTest extends \PHPUnit\Framework\TestCase
{
    public function testEmailGetterAndSetter(): void
    {
        $entity = new EmailAddressVisibility();
        $entity->setEmail('test@test.com');
        self::assertEquals('test@test.com', $entity->getEmail());
    }

    public function testOrganizationGetterAndSetter(): void
    {
        $organization = new Organization();
        $entity = new EmailAddressVisibility();
        $entity->setOrganization($organization);
        self::assertSame($organization, $entity->getOrganization());
    }

    public function testVisibleGetterAndSetter(): void
    {
        $entity = new EmailAddressVisibility();
        $entity->setIsVisible(true);
        self::assertTrue($entity->isVisible());
    }
}
