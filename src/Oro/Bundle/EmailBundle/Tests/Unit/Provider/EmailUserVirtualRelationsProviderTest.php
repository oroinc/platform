<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Provider\EmailUserVirtualRelationsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserVirtualRelationsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var EmailUserVirtualRelationsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $associationManager = $this->createMock(AssociationManager::class);
        $associationManager->expects($this->any())
            ->method('getAssociationTargets')
            ->with(
                Email::class,
                null,
                RelationType::MANY_TO_MANY,
                ActivityScope::ASSOCIATION_KIND
            )
            ->willReturn([User::class => 'target_field']);

        $this->provider = new EmailUserVirtualRelationsProvider($associationManager, $this->configProvider);
    }

    public function testIsVirtualRelationWithNotSupportedClass(): void
    {
        self::assertFalse($this->provider->isVirtualRelation('Some\Test\Class', 'testField'));
    }

    public function testIsVirtualRelationWithNotSupportedField(): void
    {
        self::assertFalse($this->provider->isVirtualRelation(EmailUser::class, 'testField'));
    }

    public function testIsVirtualRelation(): void
    {
        self::assertTrue($this->provider->isVirtualRelation(EmailUser::class, 'target_field'));
    }

    public function testGetVirtualRelationQuery(): void
    {
        self::assertEquals(
            [
                'join' => [
                    'left' => [
                        [
                            'join' => 'entity.email',
                            'alias' => 'target_field_al',
                            'conditionType' => 'WITH'
                        ],
                        [
                            'join' => 'target_field_al.target_field',
                            'alias' => 'target_field',
                            'conditionType' => 'WITH'
                        ]
                    ]
                ]
            ],
            $this->provider->getVirtualRelationQuery(EmailUser::class, 'target_field')
        );
    }

    public function testGetVirtualRelationsOnNonSupportedClass(): void
    {
        self::assertEquals([], $this->provider->getVirtualRelations('Some\Test\Class'));
    }

    public function testGetVirtualRelations(): void
    {
        $userConfig = new Config(
            new EntityConfigId('entity', User::class),
            [
                'label' => 'user label'
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(User::class)
            ->willReturn($userConfig);

        self::assertEquals(
            [
                'target_field' => [
                    'label' => 'user label',
                    'relation_type' => 'manyToMany',
                    'related_entity_name' => User::class,
                    'target_join_alias' => 'target_field',
                    'query' =>[
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'entity.email',
                                    'alias' => 'target_field_al',
                                    'conditionType' => 'WITH'
                                ],
                                [
                                    'join' => 'target_field_al.target_field',
                                    'alias' => 'target_field',
                                    'conditionType' => 'WITH'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->provider->getVirtualRelations(EmailUser::class)
        );
    }

    public function testGetTargetJoinAlias(): void
    {
        self::assertEquals('test', $this->provider->getTargetJoinAlias(EmailUser::class, 'test'));
    }
}
