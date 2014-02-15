<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\Repository\FoundEntityConfigRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\Repository\NotFoundEntityConfigRepository;

class ConfigModelManagerTest extends OrmTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ConfigModelManager
     */
    protected $configModelManager;

    public function setUp()
    {
        $this->em = $this->getTestEntityManager();

        $this->em->getConfiguration()->setEntityNamespaces(
            array(
                'OroEntityConfigBundle' => 'Oro\\Bundle\\EntityConfigBundle\\Entity',
                'Fixture'               => 'Oro\\Bundle\\EntityConfigBundle\\Tests\\Unit\\Fixture'
            )
        );

        $reader         = new AnnotationReader;
        $metadataDriver = new AnnotationDriver(
            $reader,
            __DIR__ . '/Fixture'
        );
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($this->em));

        $this->configModelManager = new ConfigModelManager($serviceLink);

    }

    public function testGetEntityManager()
    {
        $this->assertEquals($this->em, $this->configModelManager->getEntityManager());
    }

    public function testCheckDatabaseFalse()
    {
        $schema = $this->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\SchemaManagerMock')
            ->disableOriginalConstructor()
            ->getMock();

        $schema->expects($this->any())->method('listTableNames')->will($this->returnValue(array()));
        $this->em->getConnection()->getDriver()->setSchemaManager($schema);

        $this->assertFalse($this->configModelManager->checkDatabase());
    }

    public function testCheckDatabaseException()
    {
        $schema = $this->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\SchemaManagerMock')
            ->disableOriginalConstructor()
            ->getMock();

        $schema->expects($this->any())->method('listTableNames')->will($this->throwException(new \PDOException()));
        $this->em->getConnection()->getDriver()->setSchemaManager($schema);

        $this->assertFalse($this->configModelManager->checkDatabase());
    }

    public function testCheckDatabase()
    {
        $schema = $this->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\SchemaManagerMock')
            ->disableOriginalConstructor()
            ->getMock();

        $schema->expects($this->any())->method('listTableNames')->will(
            $this->returnValue(
                array(
                    'oro_entity_config',
                    'oro_entity_config_field',
                    'oro_entity_config_value',
                )
            )
        );
        $this->em->getConnection()->getDriver()->setSchemaManager($schema);

        $this->assertTrue($this->configModelManager->checkDatabase());
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testFindEntityModelEmptyClassName($className)
    {
        $this->assertNull($this->configModelManager->findEntityModel($className));
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testFindFieldModelEmptyClassName($className)
    {
        $this->assertNull($this->configModelManager->findFieldModel($className, 'testField'));
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testFindFieldModelEmptyFieldName($fieldName)
    {
        $this->assertNull($this->configModelManager->findFieldModel(DemoEntity::ENTITY_NAME, $fieldName));
    }

    public function testFindEntityModelIgnore()
    {
        $this->assertNull(
            $this->configModelManager->findEntityModel('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
        );
    }

    public function testFindFieldModelIgnore()
    {
        $this->assertNull(
            $this->configModelManager->findFieldModel(
                'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel',
                'testField'
            )
        );
    }

    public function testFindEntityModel()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())->method('getEntityState')
            ->will($this->returnValue(UnitOfWork::STATE_MANAGED));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigEntity(),
            $configModelManager->findEntityModel(DemoEntity::ENTITY_NAME)
        );

        //test localCache
        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigEntity(),
            $configModelManager->findEntityModel(DemoEntity::ENTITY_NAME)
        );
    }

    public function testFindEntityModelDetached()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->exactly(2))->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())->method('getEntityState')
            ->will($this->returnValue(UnitOfWork::STATE_DETACHED));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigEntity(),
            $configModelManager->findEntityModel(DemoEntity::ENTITY_NAME)
        );

        //test localCache
        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigEntity(),
            $configModelManager->findEntityModel(DemoEntity::ENTITY_NAME)
        );
    }

    public function testFindFieldModel()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())->method('getEntityState')
            ->will($this->returnValue(UnitOfWork::STATE_MANAGED));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigField(),
            $configModelManager->findFieldModel(DemoEntity::ENTITY_NAME, 'testField')
        );

        //test localCache
        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigField(),
            $configModelManager->findFieldModel(DemoEntity::ENTITY_NAME, 'testField')
        );

        //test localCache for another field
        $this->assertNull(
            $configModelManager->findFieldModel(DemoEntity::ENTITY_NAME, 'testField1')
        );
    }

    public function testFindFieldModelDetached()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->exactly(2))->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())->method('getEntityState')
            ->will($this->returnValue(UnitOfWork::STATE_DETACHED));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigField(),
            $configModelManager->findFieldModel(DemoEntity::ENTITY_NAME, 'testField')
        );

        //test localCache
        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigField(),
            $configModelManager->findFieldModel(DemoEntity::ENTITY_NAME, 'testField')
        );

        //test localCache for another field
        $this->assertNull(
            $configModelManager->findFieldModel(DemoEntity::ENTITY_NAME, 'testField1')
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testGetEntityModelEmptyClassName($className)
    {
        $this->configModelManager->getEntityModel($className);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     */
    public function testGetEntityModelEntityException()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())->method('getRepository')
            ->will($this->returnValue(new NotFoundEntityConfigRepository($em, $meta)));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $configModelManager->getEntityModel(DemoEntity::ENTITY_NAME);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     */
    public function testGetFieldModelFieldException()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())->method('getRepository')
            ->will($this->returnValue(new NotFoundEntityConfigRepository($em, $meta)));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $configModelManager->getFieldModel(DemoEntity::ENTITY_NAME, 'testField');
    }

    public function testGetEntityModel()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigEntity(),
            $configModelManager->getEntityModel(DemoEntity::ENTITY_NAME)
        );
    }

    public function testGetModelByConfigId()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $this->assertEquals(
            FoundEntityConfigRepository::getResultConfigEntity(),
            $configModelManager->getModelByConfigId(new EntityConfigId('test', DemoEntity::ENTITY_NAME))
        );
    }

    public function testGetModels()
    {
        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $this->assertEquals(
            array(FoundEntityConfigRepository::getResultConfigEntity()),
            $configModelManager->getModels()
        );

        $this->assertEquals(
            array(FoundEntityConfigRepository::getResultConfigField()),
            $configModelManager->getModels(DemoEntity::ENTITY_NAME)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateEntityModelException()
    {
        $this->configModelManager->createEntityModel(DemoEntity::ENTITY_NAME, 'wrongMode');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateFieldModelException()
    {
        $this->configModelManager->createFieldModel(
            DemoEntity::ENTITY_NAME,
            'testField',
            'string',
            'wrongMode'
        );
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testCreateEntityModelEmptyClassName($className)
    {
        $expectedResult = new EntityConfigModel($className);
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);

        $result = $this->configModelManager->createEntityModel($className);
        $this->assertEquals($expectedResult, $result);

        // test that the created model is NOT stored in a local cache
        $this->setExpectedException('\InvalidArgumentException');
        $this->configModelManager->getEntityModel($className);
    }

    public function testCreateEntityModel()
    {
        $className      = DemoEntity::ENTITY_NAME;
        $expectedResult = new EntityConfigModel($className);
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);

        $result = $this->configModelManager->createEntityModel($className);
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame($result, $this->configModelManager->getEntityModel($className));
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testCreateFieldModelEmptyClassName($className)
    {
        $this->configModelManager->createFieldModel($className, 'testField', 'int');
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testCreateFieldModelEmptyFieldName($fieldName)
    {
        $className = DemoEntity::ENTITY_NAME;
        $fieldType = 'int';

        $expectedResult = new FieldConfigModel($fieldName, $fieldType);
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);

        $entityModel = FoundEntityConfigRepository::getResultConfigEntity();
        $entityModel->addField($expectedResult);

        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $result = $configModelManager->createFieldModel(
            $className,
            $fieldName,
            $fieldType,
            ConfigModelManager::MODE_DEFAULT
        );
        $this->assertEquals($expectedResult, $result);

        // test that the created model is NOT stored in a local cache
        $this->setExpectedException('\InvalidArgumentException');
        $this->assertNull($configModelManager->getFieldModel($className, $fieldName));
    }

    public function testCreateFieldModel()
    {
        $className = DemoEntity::ENTITY_NAME;
        $fieldName = 'testField';
        $fieldType = 'int';

        $expectedResult = new FieldConfigModel($fieldName, $fieldType);
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);

        $entityModel = FoundEntityConfigRepository::getResultConfigEntity();
        $entityModel->addField($expectedResult);

        $meta = $this->em->getClassMetadata(EntityConfigModel::ENTITY_NAME);
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())->method('getEntityState')
            ->will($this->returnValue(UnitOfWork::STATE_MANAGED));

        $em->expects($this->any())->method('getRepository')
            ->will($this->returnValue(new FoundEntityConfigRepository($em, $meta)));

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())->method('getService')->will($this->returnValue($em));

        $configModelManager = new ConfigModelManager($serviceLink);

        $result = $configModelManager->createFieldModel(
            $className,
            $fieldName,
            $fieldType,
            ConfigModelManager::MODE_DEFAULT
        );
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame($result, $configModelManager->getFieldModel($className, $fieldName));
    }

    public function emptyNameProvider()
    {
        return [
            [null],
            [''],
        ];
    }
}
