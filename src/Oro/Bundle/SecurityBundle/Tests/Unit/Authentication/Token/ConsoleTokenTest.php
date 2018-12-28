<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;

class ConsoleTokenTest extends \PHPUnit\Framework\TestCase
{
    use OrganizationContextTrait;

    /**
     * @var ConsoleToken
     */
    protected $token;

    protected function setUp()
    {
        $this->token = new ConsoleToken();
    }

    public function testGetCredentials()
    {
        $this->assertEmpty($this->token->getCredentials());
    }

    public function testSetGetOrganizationContext()
    {
        $this->assertEmpty($this->token->getOrganizationContext());

        $organization = new Organization();
        $organization->setName('test');

        $this->token->setOrganizationContext($organization);

        $this->assertEquals($organization, $this->token->getOrganizationContext());
    }

    public function testOrganizationContextSerialization(): void
    {
        $token = new ConsoleToken();

        $this->assertOrganizationContextSerialization($token);
    }
}
