<?php
namespace Oro\Bundle\LDAPBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\DataGridBundle\Common\Object as ConfigObject;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;

class ChannelManagerProviderTest extends \PHPUnit_Framework_TestCase
{
    private $managerProvider;
    private $registry;
    private $em;

    private function mockChannel($id, $enabled = true, $export = true)
    {
        $transport = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Transport')
            ->disableOriginalConstructor()
            ->getMock();

        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue(new ParameterBag([
                'server_hostname' => '127.0.0.1',
                'server_port' => 389,
                'server_encryption' => 'none',
                'server_base_dn' => 'dc=domain,dc=local',
                'admin_dn' => 'cn=admin,dc=domain,dc=local',
                'admin_password' => 'some-password',
            ])));

        $channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $channel->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue($enabled));

        $channel->expects($this->any())
            ->method('getMappingSettings')
            ->will($this->returnValue(ConfigObject::create([
                'user_filter' => 'objectClass=inetOrgPerson',
                'role_filter' => 'objectClass=groupOfNames',
                'role_id_attribute' => 'cn',
                'role_user_id_attribute' => 'member',
                'export_user_base_dn' => 'ou=users,dc=domain,dc=local',
                'export_user_class' => 'inetOrgPerson',
                'export_auto_enable' => $export,
                'role_mapping' => [
                    [
                        'ldapName' => 'role1',
                        'crmRoles' => [1],
                    ],
                ],
                'user_mapping' => [
                    'title'    => null,
                    'email'    => 'mail',
                    'username' => 'cn',
                ]
            ])));

        return $channel;
    }

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerProvider = new ChannelManagerProvider($this->registry, $userManager);
    }

    public function testChannelReturnsSameInstanceForSameChannel()
    {
        $channel = $this->mockChannel(1);
        $firstResult = $this->managerProvider->channel($channel);
        $secondResult = $this->managerProvider->channel($channel);

        $this->assertSame($firstResult, $secondResult);
    }

    public function testChannelReturnsDifferentInstancesForDifferentChannels()
    {
        $channelOne = $this->mockChannel(1);
        $channelOneFirst = $this->managerProvider->channel($channelOne);

        $channelTwo = $this->mockChannel(5);
        $channelTwoFirst = $this->managerProvider->channel($channelTwo);

        $channelOneSecond = $this->managerProvider->channel($channelOne);
        $channelTwoSecond = $this->managerProvider->channel($channelTwo);

        $this->assertSame($channelOneFirst, $channelOneSecond);
        $this->assertSame($channelTwoFirst, $channelTwoSecond);
    }
}
