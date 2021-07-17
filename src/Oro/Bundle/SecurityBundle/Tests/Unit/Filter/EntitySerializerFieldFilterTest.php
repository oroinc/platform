<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Filter;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Filter\EntitySerializerFieldFilter;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser as Entity;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EntitySerializerFieldFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
    }

    private function getFieldFilter(bool $isIdFieldProtected = true): EntitySerializerFieldFilter
    {
        return new EntitySerializerFieldFilter(
            $this->authorizationChecker,
            $this->configManager,
            $this->doctrineHelper,
            $isIdFieldProtected
        );
    }

    public function testForIdentityFieldWhenItIsNotProtected()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'id';

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdFieldName')
            ->with($entityClass)
            ->willReturn($field);
        $this->configManager->expects(self::never())
            ->method('hasConfig');
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertNull(
            $this->getFieldFilter(false)->checkField($entity, $entityClass, $field)
        );
    }

    public function testForNotIdentityFieldWhenIdentityFieldIsNotProtected()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'test';

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdFieldName')
            ->with($entityClass)
            ->willReturn('id');
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertNull(
            $this->getFieldFilter(false)->checkField($entity, $entityClass, $field)
        );
    }

    public function testForNotConfigurableEntity()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'test';

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdFieldName');
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertNull(
            $this->getFieldFilter()->checkField($entity, $entityClass, $field)
        );
    }

    public function testForEntityThatDoesNotSupportFieldAcl()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'test';
        $securityConfig = new Config(
            $this->createMock(EntityConfigId::class),
            []
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdFieldName');
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($securityConfig);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertNull(
            $this->getFieldFilter()->checkField($entity, $entityClass, $field)
        );
    }

    public function testForEntityThatSupportsFieldAclButItIsDisabled()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'test';
        $securityConfig = new Config(
            $this->createMock(EntityConfigId::class),
            ['field_acl_supported' => true, 'field_acl_enabled' => false]
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdFieldName');
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($securityConfig);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertNull(
            $this->getFieldFilter()->checkField($entity, $entityClass, $field)
        );
    }

    public function testWhenAccessToFieldIsGranted()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'test';
        $securityConfig = new Config(
            $this->createMock(EntityConfigId::class),
            ['field_acl_supported' => true, 'field_acl_enabled' => true]
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdFieldName');
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($securityConfig);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $field))
            ->willReturn(true);

        self::assertNull(
            $this->getFieldFilter()->checkField($entity, $entityClass, $field)
        );
    }

    public function testWhenAccessToFieldValueIsDenied()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'test';
        $securityConfig = new Config(
            $this->createMock(EntityConfigId::class),
            ['field_acl_supported' => true, 'field_acl_enabled' => true]
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdFieldName');
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($securityConfig);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $field))
            ->willReturn(false);

        self::assertFalse(
            $this->getFieldFilter()->checkField($entity, $entityClass, $field)
        );
    }

    public function testWhenAccessToFieldIsDenied()
    {
        $entity = new Entity();
        $entityClass = Entity::class;
        $field = 'test';
        $securityConfig = new Config(
            $this->createMock(EntityConfigId::class),
            ['field_acl_supported' => true, 'field_acl_enabled' => true, 'show_restricted_fields' => false]
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdFieldName');
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($securityConfig);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $field))
            ->willReturn(false);

        self::assertTrue(
            $this->getFieldFilter()->checkField($entity, $entityClass, $field)
        );
    }
}
