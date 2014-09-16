<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\SystemVariablesProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SystemVariablesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dateTimeFormatter;

    /** @var SystemVariablesProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    protected function setUp()
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SystemVariablesProvider(
            $translator,
            $this->configManager,
            $this->dateTimeFormatter,
            $this->securityContext
        );
    }

    public function testGetVariableDefinitions()
    {
        $this->securityContext->expects($this->never())->method('getToken');

        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'appOrganizationName' => [
                    'type' => 'string',
                    'label' => 'oro.email.emailtemplate.app_organization_name'
                ],
                'currentDate'  => ['type' => 'string', 'label' => 'oro.email.emailtemplate.current_date'],
                'currentTime'  => ['type' => 'string', 'label' => 'oro.email.emailtemplate.current_time'],
                'appURL'       => ['type' => 'string', 'label' => 'oro.email.emailtemplate.app_url'],
            ],
            $result
        );
    }

    public function testGetVariableValues()
    {
        $organization = new Organization();
        $organization->setName('TestOrganization');
        $token = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
            ->disableOriginalConstructor()
            ->getMock();
        $token
            ->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));
        $this->securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));


        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_ui.organization_name', false, false, 'TestOrganization'],
                        ['oro_ui.application_name', false, false, ''],
                        ['oro_ui.application_title', false, false, ''],
                        ['oro_ui.application_url', false, false, 'http://localhost/'],
                    ]
                )
            );
        $this->dateTimeFormatter->expects($this->once())
            ->method('formatDate')
            ->with($this->isInstanceOf('\DateTime'))
            ->will($this->returnValue('date'));
        $this->dateTimeFormatter->expects($this->once())
            ->method('formatTime')
            ->with($this->isInstanceOf('\DateTime'))
            ->will($this->returnValue('time'));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'appShortName' => '',
                'appFullName'  => '',
                'appOrganizationName' => 'TestOrganization',
                'appURL'       => 'http://localhost/',
                'currentDate'  => 'date',
                'currentTime'  => 'time',
            ],
            $result
        );
    }
}
