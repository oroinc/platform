<?php
namespace Oro\Bundle\LDAPBundle\Tests\Unit\ImportExport;

trait MocksChannelAndContext
{
    private $channelId = 1;
    protected $userManager;
    protected $contextRegistry;
    protected $contextMediator;
    protected $managerProvider;
    protected $ldapManager;
    protected $channel;
    protected $context;

    private function mockContext()
    {
        $this->context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->any())
            ->method('getOption')
            ->with($this->equalTo('channel'))
            ->will($this->returnValue($this->channelId));
    }

    private function mockChannel()
    {
        $this->channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($this->channelId));

        $this->channel->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Channel Name'));
    }

    private function mockUserManager()
    {
        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $sm = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->userManager->expects($this->any())
            ->method('getStorageManager')
            ->will($this->returnValue($sm));
    }

    private function mockContextRegistry()
    {
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->will($this->returnValue($this->context));
    }

    private function mockContextMediator()
    {
        $this->contextMediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMediator->expects($this->any())
            ->method('getChannel')
            ->with($this->equalTo($this->context))
            ->will($this->returnValue($this->channel));
    }

    private function mockLdapManager()
    {
        $this->ldapManager = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\LdapManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ldapManager->expects($this->any())
            ->method('getUsernameAttr')
            ->will($this->returnValue('username_attr'));
    }

    private function mockChannelManagerProvider()
    {
        $this->managerProvider = $this->getMockBuilder('Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerProvider->expects($this->any())
            ->method('channel')
            ->will($this->returnValue($this->ldapManager));
    }
}
