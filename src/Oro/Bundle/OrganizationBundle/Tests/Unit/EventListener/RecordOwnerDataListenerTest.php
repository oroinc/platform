<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\EventListener\RecordOwnerDataListener;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RecordOwnerDataListenerTest extends \PHPUnit\Framework\TestCase
{
    /**  @var RecordOwnerDataListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->listener = new RecordOwnerDataListener($this->tokenAccessor, $this->configProvider);
    }

    /**
     * @dataProvider preSetData
     */
    public function testPrePersistUser($token, $securityConfig, $expect)
    {
        $entity = new Entity();

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($token->getUser());
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($token instanceof OrganizationAwareTokenInterface ? $token->getOrganization() : null);

        $args = new LifecycleEventArgs($entity, $this->createMock('Doctrine\Common\Persistence\ObjectManager'));
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($securityConfig);

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
        $organization1 = new Organization();
        $organization1->getId(4);

        $businessUnit = new BusinessUnit();
        $businessUnit->setOrganization($organization);
        $businessUnit1 = new BusinessUnit();
        $businessUnit1->setOrganization($organization1);

        $user->addBusinessUnit($businessUnit);
        $user->addBusinessUnit($businessUnit1);

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
                ['owner' => $businessUnit, 'organization' => $organization]

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
            'OwnershipType Organization with UsernamePasswordToken' => [
                new UsernamePasswordToken($user, 'admin', 'key'),
                $organizationConfig,
                []
            ],
        ];
    }

    public function testPrePersistAnonymousToken()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->tokenAccessor->expects($this->never())
            ->method('getToken');

        $args = new LifecycleEventArgs(new \stdClass(), $this->createMock('Doctrine\Common\Persistence\ObjectManager'));

        $this->listener->prePersist($args);
    }
}
