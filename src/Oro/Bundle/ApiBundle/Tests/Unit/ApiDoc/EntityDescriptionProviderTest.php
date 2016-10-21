<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;

class EntityDescriptionProviderTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassNameProvider;

    /** @var ConfigProviderMock */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var EntityDescriptionProvider */
    protected $entityDescriptionProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->entityClassNameProvider = $this->getMock(EntityClassNameProviderInterface::class);
        $this->translator = $this->getMock(TranslatorInterface::class);

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider = new ConfigProviderMock($configManager, 'entity');

        $this->entityDescriptionProvider = new EntityDescriptionProvider(
            $this->entityClassNameProvider,
            $this->entityConfigProvider,
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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);

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

        $this->entityConfigProvider->addEntityConfig($entityClass, ['description' => $entityDescription]);
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

        $this->entityConfigProvider->addEntityConfig($entityClass, ['description' => $entityDescription]);
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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);

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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);

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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            'datetime',
            ['label' => $fieldLabel]
        );
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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            'datetime',
            ['label' => $fieldLabel]
        );
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

    public function testGetFieldDescriptionForRelatedEntity()
    {
        $entityClass = Entity\Product::class;
        $propertyPath = 'category.name';
        $fieldLabel = 'label trans key';
        $expectedValue = 'label';

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
        $this->entityConfigProvider->addFieldConfig(
            Entity\Category::class,
            'name',
            'string',
            ['label' => $fieldLabel]
        );
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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);

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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
        $this->entityConfigProvider->addFieldConfig($entityClass, $fieldName, 'datetime', []);

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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            'datetime',
            ['description' => $fieldDescription]
        );
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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            'datetime',
            ['description' => $fieldDescription]
        );
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

    public function testGetFieldDocumentationForRelatedEntity()
    {
        $entityClass = Entity\Product::class;
        $propertyPath = 'category.name';
        $fieldDescription = 'description trans key';
        $expectedValue = 'description';

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
        $this->entityConfigProvider->addFieldConfig(
            Entity\Category::class,
            'name',
            'string',
            ['description' => $fieldDescription]
        );
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

        $this->entityConfigProvider->addEntityConfig($entityClass, []);
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
}
