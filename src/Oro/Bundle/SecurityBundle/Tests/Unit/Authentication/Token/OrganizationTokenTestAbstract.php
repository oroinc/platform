<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;

abstract class OrganizationTokenTestAbstract extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationContextTokenInterface
     */
    protected $token;

    public function setUp()
    {
        $this->token = $this->getToken();
    }

    public function testOrganization()
    {
        $organization = new Organization(2);
        $this->token->setOrganizationContext($organization);
        $this->assertSame($organization, $this->token->getOrganizationContext());
    }

    public function testSerialize()
    {
        $newToken = unserialize(serialize($this->token));

        $this->assertEquals($newToken->getUser()->getId(), $this->token->getUser()->getId());
        $this->assertEquals(
            $newToken->getOrganizationContext()->getId(),
            $this->token->getOrganizationContext()->getId()
        );
    }

    public function testSerializeError()
    {
        $idBeforeSerialization = $this->token->getOrganizationContext()->getId();
        $serialized = $this->token->serialize();
        $this->token->unserialize($serialized);
        $this->assertEquals($idBeforeSerialization, $this->token->getOrganizationContext()->getId());
    }
    
    /**
     * @return OrganizationContextTokenInterface
     */
    abstract protected function getToken();
}
