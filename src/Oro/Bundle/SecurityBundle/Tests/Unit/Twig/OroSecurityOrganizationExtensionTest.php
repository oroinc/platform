<?php

namespace Oro\Bundle\SecurityBundle\Tests\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Twig\OroSecurityOrganizationExtension;
use Oro\Bundle\UserBundle\Entity\User;

class OroSecurityOrganizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroSecurityOrganizationExtension
     */
    protected $twigExtension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityContext  = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->twigExtension    = new OroSecurityOrganizationExtension($this->securityContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->securityContext);
        unset($this->twigExtension);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_security_organization_extension', $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $this->assertArrayHasKey('get_enabled_organizations', $this->twigExtension->getFunctions());
    }

    public function testGetOrganizations()
    {
        $user = new User();
        $disabledOrganization = new Organization();
        $organization = new Organization();

        $organization->setEnabled(true);

        $user->setOrganizations(new ArrayCollection(array($organization, $disabledOrganization)));
        $token = $this->getMock('Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $result = $this->twigExtension->getOrganizations();

        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
        $this->assertSame($organization, $result[0]);
    }

    public function testGetCurrentOrganizationWorks()
    {
        $organization = $this->getMock('Oro\\Bundle\\OrganizationBundle\\Entity\\Organization');

        $token = $this->getMock(
            'Oro\\Bundle\\SecurityBundle\\Authentication\\Token\\OrganizationContextTokenInterface'
        );

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->assertSame($organization, $this->twigExtension->getCurrentOrganization());
    }

    public function testGetCurrentOrganizationWorksWithNotOrganizationContextToken()
    {
        $token = $this->getMock(
            'Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface'
        );

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->never())->method($this->anything());

        $this->assertNull($this->twigExtension->getCurrentOrganization());
    }
}
