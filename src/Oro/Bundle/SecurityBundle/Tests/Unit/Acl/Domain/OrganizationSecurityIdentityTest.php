<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class OrganizationSecurityIdentityTest extends \PHPUnit_Framework_TestCase
{
    const IDENTITY_ID = 2;
    const IDENTITY_CLASS = 'Oro\Bundle\OrganizationBundle\Entity\Organization';

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithEmptyId()
    {
        new OrganizationSecurityIdentity(null, self::IDENTITY_CLASS);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithEmptyClass()
    {
        new OrganizationSecurityIdentity(self::IDENTITY_ID, null);
    }

    public function testFromOrganization()
    {
        $organization = new Organization();

        $class = new \ReflectionClass($organization);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($organization, self::IDENTITY_ID);

        $securityIdentity = OrganizationSecurityIdentity::fromOrganization($organization);
        $this->assertEquals(self::IDENTITY_ID, $securityIdentity->getId());
        $this->assertEquals(self::IDENTITY_CLASS, $securityIdentity->getClass());
    }

    public function testEqualsWithDifferentClass()
    {
        $securityIdentity = new OrganizationSecurityIdentity(self::IDENTITY_ID, self::IDENTITY_CLASS);
        $this->assertFalse(
            $securityIdentity->equals(
                $this->getMockBuilder('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface')
                    ->disableOriginalConstructor()
                    ->getMock()
            )
        );
    }

    public function testEquals()
    {
        $securityIdentity = new OrganizationSecurityIdentity(self::IDENTITY_ID, self::IDENTITY_CLASS);
        $this->assertTrue(
            $securityIdentity->equals(
                new OrganizationSecurityIdentity(self::IDENTITY_ID, self::IDENTITY_CLASS)
            )
        );
    }
}
