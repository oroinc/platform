<?php
namespace Oro\Bundle\LDAPBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\LDAPBundle\EventListener\UserBeforeRenderListener;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Oro\Bundle\UserBundle\Entity\User;

class UserBeforeRenderListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var UserBeforeRenderListener */
    private $userRenderListener;

    /** @var User */
    private $user;

    /** @var FieldConfigId */
    private $configId;

    /** @var ValueRenderEvent */
    private $event;

    /** @var ChannelManagerProvider */
    private $managerProvider;

    public function setUp()
    {
        $this->managerProvider = $this->getMockBuilder('Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userRenderListener = new UserBeforeRenderListener($this->managerProvider);

        $this->user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configId = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function setUpChannel($id, $name)
    {
        $channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $channel->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $channel;
    }

    private function setUpChannels()
    {
        return [
            1 => $this->setUpChannel(1, 'First LDAP Channel'),
            40 => $this->setUpChannel(40, 'Second LDAP Channel'),
        ];
    }

    /**
     * Listener should set mappings array to be empty if ldap_mappings field
     * of user is set to null.
     */
    public function testValueShouldBeEmptyArrayIfUserHasMappingsSetToNull()
    {
        $this->event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($this->user));

        $this->event->expects($this->once())
            ->method('getFieldConfigId')
            ->will($this->returnValue($this->configId));

        $this->configId->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('ldap_mappings'));

        $this->event->expects($this->once())
            ->method('getFieldValue')
            ->will($this->returnValue(null));

        $this->managerProvider->expects($this->once())
            ->method('getChannels')
            ->will($this->returnValue([]));

        $this->event->expects($this->once())
            ->method('setFieldViewValue')
            ->with($this->equalTo(['mappings' => [], 'template' => 'OroLDAPBundle:User:ldapMappings.html.twig']));

        $this->userRenderListener->beforeValueRender($this->event);
    }

    /**
     * If there are no channels available, mappings should be empty even if
     * there are some in ldap_mappings field of user. These mappings are outdated,
     * respective integration channels could have been deleted.
     */
    public function testValueShouldBeEmptyArrayIfThereAreNoChannels()
    {
        $this->event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($this->user));

        $this->event->expects($this->once())
            ->method('getFieldConfigId')
            ->will($this->returnValue($this->configId));

        $this->configId->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('ldap_mappings'));

        $this->event->expects($this->once())
            ->method('getFieldValue')
            ->will($this->returnValue([
                1 => 'an example of user distinguished name in channel with id 1',
                40 => 'an example of user distinguished name in channel with id 40',
            ]));

        $this->managerProvider->expects($this->once())
            ->method('getChannels')
            ->will($this->returnValue([]));

        $this->event->expects($this->once())
            ->method('setFieldViewValue')
            ->with($this->equalTo(['mappings' => [], 'template' => 'OroLDAPBundle:User:ldapMappings.html.twig']));

        $this->userRenderListener->beforeValueRender($this->event);
    }

    /**
     * Test if listener properly maps Dns to channel names.
     */
    public function testValueShouldBeEqualToArrayOfMappingsInChannels()
    {
        $this->event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($this->user));

        $this->event->expects($this->once())
            ->method('getFieldConfigId')
            ->will($this->returnValue($this->configId));

        $this->configId->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('ldap_mappings'));

        $this->event->expects($this->once())
            ->method('getFieldValue')
            ->will($this->returnValue([
                1 => 'an example of user distinguished name in channel with id 1',
                40 => 'an example of user distinguished name in channel with id 40',
            ]));

        $this->managerProvider->expects($this->once())
            ->method('getChannels')
            ->will($this->returnValue($this->setUpChannels()));

        $this->event->expects($this->once())
            ->method('setFieldViewValue')
            ->with($this->equalTo([
                'mappings' => [
                    [
                        'name' => 'First LDAP Channel',
                        'dn' => 'an example of user distinguished name in channel with id 1'
                    ],
                    [
                        'name' => 'Second LDAP Channel',
                        'dn' => 'an example of user distinguished name in channel with id 40'
                    ],
                ],
                'template' => 'OroLDAPBundle:User:ldapMappings.html.twig',
            ]));

        $this->userRenderListener->beforeValueRender($this->event);
    }

    /**
     * Listener should not modify any properties if provided entity is not User.
     */
    public function testShouldDoNothingIfEntityIsNotInstanceOfUser()
    {
        $this->user = $this->getMockBuilder('InstanceOfSomethingElse')
            ->getMock();

        $this->event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($this->user));

        $this->userRenderListener->beforeValueRender($this->event);
    }

    /**
     * Listener should not modify any properties if rendered field is not "ldap_mappings".
     */
    public function testShouldDoNothingIfFieldNameIsNotLdapMappings()
    {
        $this->event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($this->user));

        $this->event->expects($this->once())
            ->method('getFieldConfigId')
            ->will($this->returnValue($this->configId));

        $this->configId->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('a_different_field_name'));

        $this->userRenderListener->beforeValueRender($this->event);
    }
}
