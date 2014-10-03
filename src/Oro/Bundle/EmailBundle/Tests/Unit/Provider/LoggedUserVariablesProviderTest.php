<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\LoggedUserVariablesProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class LoggedUserVariablesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nameFormatter;

    /** @var LoggedUserVariablesProvider */
    protected $provider;

    protected function setUp()
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LoggedUserVariablesProvider(
            $translator,
            $this->securityFacade,
            $this->nameFormatter
        );
    }

    public function testGetVariableDefinitionsWithoutLoggedUser()
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [],
            $result
        );
    }

    public function testGetVariableDefinitionsForNonOroUser()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'userName' => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_name'],
            ],
            $result
        );
    }

    public function testGetVariableDefinitions()
    {
        $organization = new Organization();
        $user         = new User();

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'userName'         => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_name'],
                'userFirstName'    => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_first_name'],
                'userLastName'     => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_last_name'],
                'userFullName'     => ['type' => 'string', 'label' => 'oro.email.emailtemplate.user_full_name'],
                'organizationName' => ['type' => 'string', 'label' => 'oro.email.emailtemplate.organization_name'],
            ],
            $result
        );
    }

    public function testGetVariableValuesWithoutLoggedUser()
    {
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue(null));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => '',
                'userFirstName'    => '',
                'userLastName'     => '',
                'userFullName'     => '',
                'organizationName' => '',
            ],
            $result
        );
    }

    public function testGetVariableValuesForNonOroUser()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('test'));

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue(null));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => 'test',
                'userFirstName'    => '',
                'userLastName'     => '',
                'userFullName'     => '',
                'organizationName' => '',
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

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->with($this->identicalTo($user))
            ->will($this->returnValue('FullName'));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'userName'         => 'test',
                'userFirstName'    => 'FirstName',
                'userLastName'     => 'LastName',
                'userFullName'     => 'FullName',
                'organizationName' => 'TestOrg',
            ],
            $result
        );
    }
}
