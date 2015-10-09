<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\MappingException;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\DictionaryVirtualFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

class DictionaryVirtualFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $groupingConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $dictionaryConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var DictionaryVirtualFieldProvider */
    private $provider;

    protected function setUp()
    {
        $this->groupingConfigProvider   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dictionaryConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->provider = new DictionaryVirtualFieldProvider(
            $this->groupingConfigProvider,
            $this->dictionaryConfigProvider,
            $doctrine
        );
    }

    public function testDictionaryWithOneExplicitlyDeclaredVirtualFields()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->will($this->returnValue(['testRel']));
        $entityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('testRel')
            ->will($this->returnValue('Acme\TestBundle\Entity\Dictionary1'));
        $entityMetadata->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with('testRel')
            ->will($this->returnValue(true));

        $this->initialize([$entityClassName => $entityMetadata]);

        $this->assertEquals(
            ['test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );
        $dictionary = $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_name');
        $this->assertContains('t_test_rel', $dictionary['select']['expr']);
        $this->assertEquals('dictionary', $dictionary['select']['return_type']);
        $this->assertEquals('Acme\TestBundle\Entity\Dictionary1', $dictionary['select']['related_entity_name']);
        $this->assertEquals('acme.test.testentity.test_rel.label', $dictionary['select']['label']);

        $this->assertEquals('entity.testRel', $dictionary['join']['left'][0]['join']);
        $this->assertContains('t_test_rel', $dictionary['join']['left'][0]['alias']);
    }

    public function testDictionaryWithTwoExplicitlyDeclaredVirtualFields()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->will($this->returnValue(['testRel']));
        $entityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('testRel')
            ->will($this->returnValue('Acme\TestBundle\Entity\Dictionary2'));
        $entityMetadata->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with('testRel')
            ->will($this->returnValue(true));

        $this->initialize([$entityClassName => $entityMetadata]);

        $this->assertEquals(
            ['test_rel_id', 'test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_id')
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );

        $dictionary = $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_id');
        $this->assertContains('t_test_rel', $dictionary['select']['expr']);
        $this->assertContains('id', $dictionary['select']['expr']);
        $this->assertEquals('dictionary', $dictionary['select']['return_type']);
        $this->assertEquals('Acme\TestBundle\Entity\Dictionary2', $dictionary['select']['related_entity_name']);
        $this->assertEquals('acme.test.testentity.test_rel_id.label', $dictionary['select']['label']);

        $this->assertEquals('entity.testRel', $dictionary['join']['left'][0]['join']);
        $this->assertContains('t_test_rel', $dictionary['join']['left'][0]['alias']);

        $dictionary = $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_name');
        $this->assertContains('t_test_rel', $dictionary['select']['expr']);
        $this->assertContains('name', $dictionary['select']['expr']);
        $this->assertEquals('dictionary', $dictionary['select']['return_type']);
        $this->assertEquals('Acme\TestBundle\Entity\Dictionary2', $dictionary['select']['related_entity_name']);
        $this->assertEquals('acme.test.testentity.test_rel_name.label', $dictionary['select']['label']);

        $this->assertEquals('entity.testRel', $dictionary['join']['left'][0]['join']);
        $this->assertContains('t_test_rel', $dictionary['join']['left'][0]['alias']);
    }

    public function testDictionaryWithOneVirtualField()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->will($this->returnValue(['testRel']));
        $entityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('testRel')
            ->will($this->returnValue('Acme\TestBundle\Entity\Dictionary3'));
        $entityMetadata->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with('testRel')
            ->will($this->returnValue(true));

        $this->initialize([$entityClassName => $entityMetadata]);

        $this->assertEquals(
            ['test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );

        $dictionary = $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_name');
        $this->assertContains('t_test_rel', $dictionary['select']['expr']);
        $this->assertEquals('dictionary', $dictionary['select']['return_type']);
        $this->assertEquals('Acme\TestBundle\Entity\Dictionary3', $dictionary['select']['related_entity_name']);
        $this->assertEquals('acme.test.testentity.test_rel.label', $dictionary['select']['label']);

        $this->assertEquals('entity.testRel', $dictionary['join']['left'][0]['join']);
        $this->assertContains('t_test_rel', $dictionary['join']['left'][0]['alias']);
    }

    public function testDictionaryWithSeveralVirtualFields()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->will($this->returnValue(['testRel']));
        $entityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('testRel')
            ->will($this->returnValue('Acme\TestBundle\Entity\Dictionary4'));
        $entityMetadata->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with('testRel')
            ->will($this->returnValue(true));

        $this->initialize([$entityClassName => $entityMetadata]);

        $this->assertEquals(
            ['test_rel_code', 'test_rel_label'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            false,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_code')
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_label')
        );

        $dictionary = $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_code');
        $this->assertContains('t_test_rel', $dictionary['select']['expr']);
        $this->assertContains('code', $dictionary['select']['expr']);
        $this->assertEquals('dictionary', $dictionary['select']['return_type']);
        $this->assertEquals('Acme\TestBundle\Entity\Dictionary4', $dictionary['select']['related_entity_name']);
        $this->assertEquals('acme.test.testentity.test_rel_code.label', $dictionary['select']['label']);

        $this->assertEquals('entity.testRel', $dictionary['join']['left'][0]['join']);
        $this->assertContains('t_test_rel', $dictionary['join']['left'][0]['alias']);

        $dictionary = $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_label');
        $this->assertContains('t_test_rel', $dictionary['select']['expr']);
        $this->assertContains('label', $dictionary['select']['expr']);
        $this->assertEquals('dictionary', $dictionary['select']['return_type']);
        $this->assertEquals('Acme\TestBundle\Entity\Dictionary4', $dictionary['select']['related_entity_name']);
        $this->assertEquals('acme.test.testentity.test_rel_label.label', $dictionary['select']['label']);

        $this->assertEquals('entity.testRel', $dictionary['join']['left'][0]['join']);
        $this->assertContains('t_test_rel', $dictionary['join']['left'][0]['alias']);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function initialize($metadata)
    {
        $dict1GrpCfg = $this->createEntityConfig(
            'grouping',
            'Acme\TestBundle\Entity\Dictionary1',
            ['groups' => [GroupingScope::GROUP_DICTIONARY]]
        );
        $dict1Cfg    = $this->createEntityConfig(
            'dictionary',
            'Acme\TestBundle\Entity\Dictionary1',
            ['virtual_fields' => ['name']]
        );

        $dict2GrpCfg = $this->createEntityConfig(
            'grouping',
            'Acme\TestBundle\Entity\Dictionary2',
            ['groups' => [GroupingScope::GROUP_DICTIONARY]]
        );
        $dict2Cfg    = $this->createEntityConfig(
            'dictionary',
            'Acme\TestBundle\Entity\Dictionary2',
            ['virtual_fields' => ['id', 'name']]
        );

        $dict3GrpCfg = $this->createEntityConfig(
            'grouping',
            'Acme\TestBundle\Entity\Dictionary3',
            ['groups' => [GroupingScope::GROUP_DICTIONARY]]
        );
        $dict3Cfg    = $this->createEntityConfig('dictionary', 'Acme\TestBundle\Entity\Dictionary3');

        $dict4GrpCfg = $this->createEntityConfig(
            'grouping',
            'Acme\TestBundle\Entity\Dictionary4',
            ['groups' => [GroupingScope::GROUP_DICTIONARY]]
        );
        $dict4Cfg    = $this->createEntityConfig('dictionary', 'Acme\TestBundle\Entity\Dictionary4');

        $this->groupingConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will($this->returnValue([$dict1GrpCfg, $dict2GrpCfg, $dict3GrpCfg, $dict4GrpCfg]));
        $this->dictionaryConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) {
                        return in_array(
                            $className,
                            [
                                'Acme\TestBundle\Entity\Dictionary1',
                                'Acme\TestBundle\Entity\Dictionary2',
                                'Acme\TestBundle\Entity\Dictionary3',
                                'Acme\TestBundle\Entity\Dictionary4'
                            ]
                        );
                    }
                )
            );
        $this->dictionaryConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use (&$dict1Cfg, &$dict2Cfg, &$dict3Cfg, &$dict4Cfg) {
                        switch ($className) {
                            case 'Acme\TestBundle\Entity\Dictionary1':
                                return $dict1Cfg;
                            case 'Acme\TestBundle\Entity\Dictionary2':
                                return $dict2Cfg;
                            case 'Acme\TestBundle\Entity\Dictionary3':
                                return $dict3Cfg;
                            case 'Acme\TestBundle\Entity\Dictionary4':
                                return $dict4Cfg;
                            default:
                                throw new RuntimeException(sprintf('Entity "%s" is not configurable', $className));
                        }
                    }
                )
            );

        $mDataDict1 = $this->createDictionaryMetadata();
        $mDataDict2 = $this->createDictionaryMetadata();
        $mDataDict3 = $this->createDictionaryMetadata();
        $mDataDict4 = $this->createDictionaryMetadata(
            ['name' => 'string', 'code' => 'string', 'label' => 'string'],
            'name'
        );
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will(
                $this->returnCallback(
                    function ($className) use (&$metadata, &$mDataDict1, &$mDataDict2, &$mDataDict3, &$mDataDict4) {
                        switch ($className) {
                            case 'Acme\TestBundle\Entity\Dictionary1':
                                return $mDataDict1;
                            case 'Acme\TestBundle\Entity\Dictionary2':
                                return $mDataDict2;
                            case 'Acme\TestBundle\Entity\Dictionary3':
                                return $mDataDict3;
                            case 'Acme\TestBundle\Entity\Dictionary4':
                                return $mDataDict4;
                            default:
                                if (isset($metadata[$className])) {
                                    return $metadata[$className];
                                }
                                throw MappingException::reflectionFailure($className, new \ReflectionException());
                        }
                    }
                )
            );
    }

    /**
     * @param string $scope
     * @param string $className
     * @param array  $values
     * @return Config
     */
    protected function createEntityConfig($scope, $className, $values = [])
    {
        $config = new Config(new EntityConfigId($scope, $className));
        $config->setValues($values);

        return $config;
    }

    /**
     * @param array  $fields key = fieldName, value = fieldType
     * @param string $idFieldName
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createDictionaryMetadata($fields = [], $idFieldName = 'id')
    {
        if (empty($fields)) {
            $fields = ['id' => 'integer', 'name' => 'string'];
        }
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getFieldNames')
            ->will($this->returnValue(array_keys($fields)));
        $metadata->expects($this->any())
            ->method('isIdentifier')
            ->will(
                $this->returnCallback(
                    function ($fieldName) use (&$idFieldName) {
                        return $fieldName === $idFieldName;
                    }
                )
            );
        $metadata->expects($this->any())
            ->method('getTypeOfField')
            ->will(
                $this->returnCallback(
                    function ($fieldName) use (&$fields) {
                        return $fields[$fieldName];
                    }
                )
            );

        return $metadata;
    }
}
