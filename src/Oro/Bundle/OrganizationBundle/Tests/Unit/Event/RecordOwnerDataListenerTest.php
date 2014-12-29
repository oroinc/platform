<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Event;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

use Oro\Bundle\OrganizationBundle\Event\RecordOwnerDataListener;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;

use Oro\Bundle\EntityConfigBundle\Config\Config;

class RecordOwnerDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /**  @var RecordOwnerDataListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    protected function setUp()
    {
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')
            ->will($this->returnValue($this->securityContext));
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RecordOwnerDataListener($serviceLink, $this->configProvider);
    }

    /**
     * @dataProvider preSetData
     */
    public function testPrePersistUser($token, $securityConfig, $expect)
    {
        $entity = new Entity();
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $args = new LifecycleEventArgs($entity, $this->getMock('Doctrine\Common\Persistence\ObjectManager'));
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($securityConfig));

        $this->listener->prePersist($args);
        if (isset($expect['owner'])) {
            $this->assertEquals($expect['owner'], $entity->getOwner());
        } else {
            $this->assertNull($entity->getOwner());
        }
        if (isset($expect['organization'])) {
            $this->assertEquals($expect['organization'], $entity->getOrganization());
        } else {
            $this->assertNull($entity->getOrganization());
        }
    }

    public function preSetData()
    {
        $entityConfigId = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId')
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User();
        $user->setId(1);

        $organization = new Organization();
        $organization->setId(3);

        $userConfig = new Config($entityConfigId);
        $userConfig->setValues(
            [
                "owner_type" => "USER",
                "owner_field_name" => "owner",
                "owner_column_name" => "owner_id",
                "organization_field_name" => "organization",
                "organization_column_name" => "organization_id"
            ]
        );
        $buConfig = new Config($entityConfigId);
        $buConfig->setValues(
            [
                "owner_type" => "BUSINESS_UNIT",
                "owner_field_name" => "owner",
                "owner_column_name" => "owner_id",
                "organization_field_name" => "organization",
                "organization_column_name" => "organization_id"
            ]
        );
        $organizationConfig = new Config($entityConfigId);
        $organizationConfig->setValues(
            ["owner_type" => "ORGANIZATION", "owner_field_name" => "owner", "owner_column_name" => "owner_id"]
        );

        return [
            'OwnershipType User with UsernamePasswordOrganizationToken' => [
                new UsernamePasswordOrganizationToken($user, 'admin', 'key', $organization),
                $userConfig,
                ['owner' => $user, 'organization' => $organization]
            ],
            'OwnershipType BusinessUnit with UsernamePasswordOrganizationToken' => [
                new UsernamePasswordOrganizationToken($user, 'admin', 'key', $organization),
                $buConfig,
                ['organization' => $organization]

            ],
            'OwnershipType Organization with UsernamePasswordOrganizationToken' => [
                new UsernamePasswordOrganizationToken($user, 'admin', 'key', $organization),
                $organizationConfig,
                ['owner' => $organization]
            ],
            'OwnershipType User with UsernamePasswordToken' => [
                new UsernamePasswordToken($user, 'admin', 'key'),
                $userConfig,
                ['owner' => $user]
            ],
            'OwnershipType BusinessUnit with UsernamePasswordToken' => [
                new UsernamePasswordToken($user, 'admin', 'key'),
                $buConfig,
                []

            ],
            'OwnershipType Organization with UsernamePasswordToken' => [
                new UsernamePasswordToken($user, 'admin', 'key'),
                $organizationConfig,
                []
            ],
        ];
    }
}
