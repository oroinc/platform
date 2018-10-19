<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FieldAclHelperTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var FieldAclHelper */
    protected $fieldAclHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->fieldAclHelper = new FieldAclHelper(
            $this->authorizationChecker,
            $this->configManager,
            $this->doctrineHelper
        );
    }

    public function testIsFieldAclEnabledForNotManageableEntity()
    {
        $entityClass = 'Test\Class';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse($this->fieldAclHelper->isFieldAclEnabled($entityClass));
    }

    public function testIsFieldAclEnabledForNotConfigurableEntity()
    {
        $entityClass = 'Test\Class';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        self::assertFalse($this->fieldAclHelper->isFieldAclEnabled($entityClass));
    }

    public function testIsFieldAclEnabledWhenFieldAclIsNotSupported()
    {
        $entityClass = 'Test\Class';
        $entityConfig = new Config(
            new EntityConfigId('security', $entityClass),
            [
                'field_acl_supported' => false
            ]
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->fieldAclHelper->isFieldAclEnabled($entityClass));
    }

    public function testIsFieldAclEnabledWhenFieldAclIsNotEnabled()
    {
        $entityClass = 'Test\Class';
        $entityConfig = new Config(
            new EntityConfigId('security', $entityClass),
            [
                'field_acl_supported' => true,
                'field_acl_enabled'   => false
            ]
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->fieldAclHelper->isFieldAclEnabled($entityClass));
    }

    public function testIsFieldAclEnabledWhenFieldAclIsEnabled()
    {
        $entityClass = 'Test\Class';
        $entityConfig = new Config(
            new EntityConfigId('security', $entityClass),
            [
                'field_acl_supported' => true,
                'field_acl_enabled'   => true
            ]
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($entityConfig);

        self::assertTrue($this->fieldAclHelper->isFieldAclEnabled($entityClass));
    }

    public function testIsRestrictedFieldsVisibleForNotManageableEntity()
    {
        $entityClass = 'Test\Class';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);

        self::assertTrue($this->fieldAclHelper->isRestrictedFieldsVisible($entityClass));
    }

    public function testIsRestrictedFieldsVisibleForNotConfigurableEntity()
    {
        $entityClass = 'Test\Class';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        self::assertTrue($this->fieldAclHelper->isRestrictedFieldsVisible($entityClass));
    }

    public function testIsRestrictedFieldsVisibleWhenFieldIsNotVisible()
    {
        $entityClass = 'Test\Class';
        $entityConfig = new Config(
            new EntityConfigId('security', $entityClass),
            [
                'show_restricted_fields' => false
            ]
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->fieldAclHelper->isRestrictedFieldsVisible($entityClass));
    }

    public function testIsFieldAclEnabledWhenFieldIsVisible()
    {
        $entityClass = 'Test\Class';
        $entityConfig = new Config(
            new EntityConfigId('security', $entityClass),
            [
                'show_restricted_fields' => true
            ]
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($entityConfig);

        self::assertTrue($this->fieldAclHelper->isRestrictedFieldsVisible($entityClass));
    }

    public function testIsFieldViewGrantedForUnsupportedEntityType()
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertTrue($this->fieldAclHelper->isFieldViewGranted(null, 'city'));
    }

    public function testIsFieldViewGranted()
    {
        $entity = new CmsAddress();
        $fieldName = 'city';

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                'VIEW',
                new FieldVote($entity, $fieldName)
            )
            ->willReturn(false);

        self::assertFalse($this->fieldAclHelper->isFieldViewGranted($entity, $fieldName));
    }

    public function testIsFieldModificationGrantedForUnsupportedEntityType()
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertTrue($this->fieldAclHelper->isFieldModificationGranted(null, 'city'));
    }

    public function testIsFieldModificationGrantedForNewEntity()
    {
        $entity = new CmsAddress();
        $fieldName = 'city';

        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(true);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                'CREATE',
                new FieldVote($entity, $fieldName)
            )
            ->willReturn(false);

        self::assertFalse($this->fieldAclHelper->isFieldModificationGranted($entity, $fieldName));
    }

    public function testIsFieldModificationGrantedForExistingEntity()
    {
        $entity = new CmsAddress();
        $fieldName = 'city';

        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(false);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                'EDIT',
                new FieldVote($entity, $fieldName)
            )
            ->willReturn(false);

        self::assertFalse($this->fieldAclHelper->isFieldModificationGranted($entity, $fieldName));
    }

    public function testAddFieldModificationDeniedFormErrorToFieldWithoutExistingErrors()
    {
        $form = $this->factory->create(FormType::class, new CmsAddress(), ['data_class' => CmsAddress::class]);
        $form->add('city');

        $this->fieldAclHelper->addFieldModificationDeniedFormError($form->get('city'));

        /** @var FormError[] $errors */
        $errors = $form->get('city')->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals(
            'You have no access to modify this field.',
            $errors[0]->getMessage()
        );
        $this->assertInstanceOf(
            FieldAccessGranted::class,
            $errors[0]->getCause()->getConstraint()
        );
    }

    public function testAddFieldModificationDeniedFormErrorToFieldWithExistingErrors()
    {
        $form = $this->factory->create(FormType::class, new CmsAddress(), ['data_class' => CmsAddress::class]);
        $form->add('city')->addError(new FormError('city error'));

        $this->fieldAclHelper->addFieldModificationDeniedFormError($form->get('city'));

        /** @var FormError[] $errors */
        $errors = $form->get('city')->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals(
            'You have no access to modify this field.',
            $errors[0]->getMessage()
        );
        $this->assertInstanceOf(
            FieldAccessGranted::class,
            $errors[0]->getCause()->getConstraint()
        );
    }
}
