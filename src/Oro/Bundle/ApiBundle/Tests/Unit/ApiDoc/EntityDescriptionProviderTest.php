<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EntityDescriptionProviderTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityClassNameProviderInterface */
    private $entityClassNameProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var EntityDescriptionProvider */
    private $entityDescriptionProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->entityClassNameProvider = $this->createMock(EntityClassNameProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->entityDescriptionProvider = new EntityDescriptionProvider(
            $this->entityClassNameProvider,
            $this->configManager,
            $this->doctrineHelper,
            $this->translator
        );
    }

    public function testGetEntityDescription()
    {
        $entityClass = 'Test\Class';
        $humanReadableClassName = 'test name';

        $this->entityClassNameProvider->expects(self::once())
            ->method('getEntityClassName')
            ->with($entityClass)
            ->willReturn($humanReadableClassName);

        self::assertEquals(
            $humanReadableClassName,
            $this->entityDescriptionProvider->getEntityDescription($entityClass)
        );

        // test that the result is cached
        self::assertEquals(
            $humanReadableClassName,
            $this->entityDescriptionProvider->getEntityDescription($entityClass)
        );
    }

    public function testGetEntityPluralDescription()
    {
        $entityClass = 'Test\Class';
        $humanReadableClassName = 'test name';

        $this->entityClassNameProvider->expects(self::once())
            ->method('getEntityClassPluralName')
            ->with($entityClass)
            ->willReturn($humanReadableClassName);

        self::assertEquals(
            $humanReadableClassName,
            $this->entityDescriptionProvider->getEntityPluralDescription($entityClass)
        );

        // test that the result is cached
        self::assertEquals(
            $humanReadableClassName,
            $this->entityDescriptionProvider->getEntityPluralDescription($entityClass)
        );
    }

    public function testNoCollisionsBetweenGetEntityDescriptionAndPluralDescription()
    {
        $entityClass = 'Test\Class';
        $humanReadableClassName = 'test name';
        $humanReadableClassPluralName = 'test plural name';

        $this->entityClassNameProvider->expects(self::once())
            ->method('getEntityClassName')
            ->with($entityClass)
            ->willReturn($humanReadableClassName);
        $this->entityClassNameProvider->expects(self::once())
            ->method('getEntityClassPluralName')
            ->with($entityClass)
            ->willReturn($humanReadableClassPluralName);

        self::assertEquals(
            $humanReadableClassName,
            $this->entityDescriptionProvider->getEntityDescription($entityClass)
        );
        self::assertEquals(
            $humanReadableClassPluralName,
            $this->entityDescriptionProvider->getEntityPluralDescription($entityClass)
        );
    }

    public function testGetEntityDocumentationForNotConfigurableEntity()
    {
        $entityClass = Entity\Product::class;
        $expectedValue = null;

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );
    }

    public function testGetEntityDocumentationForConfigurableEntityWithoutDescription()
    {
        $entityClass = Entity\Product::class;
        $expectedValue = null;

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('entity', $entityClass)
            ->willReturn($this->getEntityConfig($entityClass, []));

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );
    }

    public function testGetEntityDocumentationForConfigurableEntityWithMissingTranslation()
    {
        $entityClass = Entity\Product::class;
        $entityDescription = 'description trans key';
        $expectedValue = null;

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('entity', $entityClass)
            ->willReturn($this->getEntityConfig($entityClass, ['description' => $entityDescription]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($entityDescription)
            ->willReturn($entityDescription);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );
    }

    public function testGetEntityDocumentationForConfigurableEntityWhenTranslationExists()
    {
        $entityClass = Entity\Product::class;
        $entityDescription = 'description trans key';
        $expectedValue = 'description';

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('entity', $entityClass)
            ->willReturn($this->getEntityConfig($entityClass, ['description' => $entityDescription]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($entityDescription)
            ->willReturn($expectedValue);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );
    }

    public function testGetEntityDocumentationForHiddenConfigurableEntity()
    {
        $entityClass = Entity\Product::class;
        $expectedValue = null;

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getEntityDocumentation($entityClass)
        );
    }

    public function testGetFieldDescriptionForNotManageableEntity()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = 'updated at';

        $this->notManageableClassNames = [$entityClass];

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForManageableEntity()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = 'updated at';

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForConfigurableEntityButNotConfigurableField()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = 'updated at';

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForConfigurableEntityWithoutFieldLabel()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = 'updated at';

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', $entityClass, $fieldName)
            ->willReturn($this->getFieldConfig($entityClass, $fieldName, []));

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForConfigurableEntityWithoutTranslationForFieldLabel()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $fieldLabel = 'label trans key';
        $expectedValue = 'updated at';

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', $entityClass, $fieldName)
            ->willReturn($this->getFieldConfig($entityClass, $fieldName, ['label' => $fieldLabel]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($fieldLabel)
            ->willReturn($fieldLabel);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForConfigurableEntityWhenTranslationForFieldLabelExists()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $fieldLabel = 'label trans key';
        $expectedValue = 'label';

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', $entityClass, $fieldName)
            ->willReturn($this->getFieldConfig($entityClass, $fieldName, ['label' => $fieldLabel]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($fieldLabel)
            ->willReturn($expectedValue);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForHiddenConfigurableEntity()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = 'updated at';

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, null)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass, null)
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForHiddenConfigurableField()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = 'updated at';

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $fieldName)
        );
    }

    public function testGetFieldDescriptionForRelatedEntity()
    {
        $entityClass = Entity\Product::class;
        $propertyPath = 'category.name';
        $fieldLabel = 'label trans key';
        $expectedValue = 'label';

        $this->configManager->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [Entity\Category::class, null, true],
                    [Entity\Category::class, 'name', true]
                ]
            );
        $this->configManager->expects(self::exactly(3))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [Entity\Category::class, null, false],
                    [Entity\Category::class, 'name', false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', Entity\Category::class, 'name')
            ->willReturn($this->getFieldConfig(Entity\Category::class, 'name', ['label' => $fieldLabel]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($fieldLabel)
            ->willReturn($expectedValue);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $propertyPath)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $propertyPath)
        );
    }

    public function testGetFieldDescriptionForRelatedNotConfigurableEntity()
    {
        $entityClass = Entity\Product::class;
        $propertyPath = 'category.name';
        $expectedValue = null;

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [Entity\Category::class, null, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $propertyPath)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDescription($entityClass, $propertyPath)
        );
    }

    public function testGetFieldDocumentationForNotManageableEntity()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = null;

        $this->notManageableClassNames = [$entityClass];

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForManageableEntity()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = null;

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForConfigurableEntityButNotConfigurableField()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = null;

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForConfigurableEntityWithoutFieldDescription()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = null;

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', $entityClass, $fieldName)
            ->willReturn($this->getFieldConfig($entityClass, $fieldName, []));

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForConfigurableEntityWithoutTranslationForFieldDescription()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $fieldDescription = 'description trans key';
        $expectedValue = null;

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', $entityClass, $fieldName)
            ->willReturn($this->getFieldConfig($entityClass, $fieldName, ['description' => $fieldDescription]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($fieldDescription)
            ->willReturn($fieldDescription);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForConfigurableEntityWhenTranslationForFieldDescriptionExists()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $fieldDescription = 'description trans key';
        $expectedValue = 'description';

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', $entityClass, $fieldName)
            ->willReturn($this->getFieldConfig($entityClass, $fieldName, ['description' => $fieldDescription]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($fieldDescription)
            ->willReturn($expectedValue);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForHiddenConfigurableEntity()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = null;

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, null)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass, null)
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForHiddenConfigurableField()
    {
        $entityClass = Entity\Product::class;
        $fieldName = 'updatedAt';
        $expectedValue = null;

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [$entityClass, $fieldName, true]
                ]
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName)
        );
    }

    public function testGetFieldDocumentationForRelatedEntity()
    {
        $entityClass = Entity\Product::class;
        $propertyPath = 'category.name';
        $fieldDescription = 'description trans key';
        $expectedValue = 'description';

        $this->configManager->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [Entity\Category::class, null, true],
                    [Entity\Category::class, 'name', true]
                ]
            );
        $this->configManager->expects(self::exactly(3))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [Entity\Category::class, null, false],
                    [Entity\Category::class, 'name', false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('entity', Entity\Category::class, 'name')
            ->willReturn($this->getFieldConfig(Entity\Category::class, 'name', ['description' => $fieldDescription]));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($fieldDescription)
            ->willReturn($expectedValue);

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $propertyPath)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $propertyPath)
        );
    }

    public function testGetFieldDocumentationForRelatedNotConfigurableEntity()
    {
        $entityClass = Entity\Product::class;
        $propertyPath = 'category.name';
        $expectedValue = null;

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [Entity\Category::class, null, false]
                ]
            );
        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $propertyPath)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $propertyPath)
        );
    }

    public function testGetFieldDocumentationForRelatedHiddenConfigurableEntity()
    {
        $entityClass = Entity\Product::class;
        $propertyPath = 'category.name';
        $expectedValue = null;

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$entityClass, null, true],
                    [Entity\Category::class, null, true]
                ]
            );
        $this->configManager->expects(self::exactly(2))
            ->method('isHiddenModel')
            ->willReturnMap(
                [
                    [$entityClass, null, false],
                    [Entity\Category::class, null, true]
                ]
            );

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $propertyPath)
        );

        // test that the result is cached
        self::assertSame(
            $expectedValue,
            $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $propertyPath)
        );
    }

    /**
     * @param string $entityClass
     * @param array  $values
     *
     * @return Config
     */
    protected function getEntityConfig($entityClass, array $values)
    {
        $config = new Config(new EntityConfigId('entity', $entityClass));
        $config->setValues($values);

        return $config;
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param array  $values
     *
     * @return Config
     */
    protected function getFieldConfig($entityClass, $fieldName, array $values)
    {
        $config = new Config(new FieldConfigId('entity', $entityClass, $fieldName));
        $config->setValues($values);

        return $config;
    }
}
