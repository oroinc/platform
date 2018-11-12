<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\LoggedUserVariablesProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class LoggedUserVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var LoggedUserVariablesProvider */
    protected $provider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    protected function setUp()
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LoggedUserVariablesProvider(
            $translator,
            $this->tokenAccessor,
            $this->entityNameResolver,
            $this->configManager
        );
    }

    public function testGetVariableDefinitionsWithoutLoggedUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(null));

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [],
            $result
        );
    }

    public function testGetVariableDefinitionsForNonOroUser()
    {
        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'userName' => [
                    'type' => 'string',
                    'label' => 'oro.email.emailtemplate.user_name'
                ],
                'userSignature' => [
                    'type' => 'string',
                    'label' => 'oro.email.emailtemplate.siganture',
                    'filter' => 'oro_html_strip_tags'
                ],
            ],
            $result
        );
    }

    public function testGetVariableDefinitions()
    {
        $organization = new Organization();
        $user         = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'userName'         => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_name'],
                'userFirstName'    => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_first_name'],
                'userLastName'     => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_last_name'],
                'userFullName'     => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_full_name'],
                'organizationName' => ['type' => 'string', 'label' => 'oro.email.emailtemplate.organization_name'],
                'userSignature'    => [
                    'type' => 'string',
                    'label' => 'oro.email.emailtemplate.siganture',
                    'filter' => 'oro_html_strip_tags'
                ],
            ],
            $result
        );
    }

    public function testGetVariableValuesWithoutLoggedUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue(null));
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(null));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => '',
                'userFirstName'    => '',
                'userLastName'     => '',
                'userFullName'     => '',
                'organizationName' => '',
                'userSignature'    => '',
            ],
            $result
        );
    }

    public function testGetVariableValuesForNonOroUser()
    {
        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('test'));

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue(null));
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => 'test',
                'userFirstName'    => '',
                'userLastName'     => '',
                'userFullName'     => '',
                'organizationName' => '',
                'userSignature'    => '',
            ],
            $result
        );
    }

    public function testGetVariableValues()
    {
        $organization = new Organization();
        $organization->setName('TestOrg');

        $user = new User();
        $user->setUsername('test');
        $user->setFirstName('FirstName');
        $user->setLastName('LastName');

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($user))
            ->will($this->returnValue('FullName'));

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_email.signature')
            ->will($this->returnValue('Signature'));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => 'test',
                'userFirstName'    => 'FirstName',
                'userLastName'     => 'LastName',
                'userFullName'     => 'FullName',
                'organizationName' => 'TestOrg',
                'userSignature'    => 'Signature',
            ],
            $result
        );
    }
}
