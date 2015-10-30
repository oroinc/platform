<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;

class BusinessUnitSecurityIdentityTest extends \PHPUnit_Framework_TestCase
{
    const IDENTITY_ID = 2;
    const IDENTITY_CLASS = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithEmptyId()
    {
        new BusinessUnitSecurityIdentity(null, self::IDENTITY_CLASS);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithEmptyClass()
    {
        new BusinessUnitSecurityIdentity(self::IDENTITY_ID, null);
    }

    public function testFromOrganization()
    {
        $organization = new BusinessUnit();

        $class = new \ReflectionClass($organization);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($organization, self::IDENTITY_ID);

        $securityIdentity = BusinessUnitSecurityIdentity::fromBusinessUnit($organization);
        $this->assertEquals(self::IDENTITY_ID, $securityIdentity->getId());
        $this->assertEquals(self::IDENTITY_CLASS, $securityIdentity->getClass());
    }

    public function testEqualsWithDifferentClass()
    {
        $securityIdentity = new BusinessUnitSecurityIdentity(self::IDENTITY_ID, self::IDENTITY_CLASS);
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
        $securityIdentity = new BusinessUnitSecurityIdentity(self::IDENTITY_ID, self::IDENTITY_CLASS);
        $this->assertTrue(
            $securityIdentity->equals(
                new BusinessUnitSecurityIdentity(self::IDENTITY_ID, self::IDENTITY_CLASS)
            )
        );
    }
}
