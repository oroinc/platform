<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EnumVirtualFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumVirtualFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var EnumVirtualFieldProvider */
    protected $provider;

    protected function setUp()
    {
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->provider = new EnumVirtualFieldProvider(
            $this->extendConfigProvider,
            $doctrine
        );
    }

    public function testGetVirtualFields()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertEquals(
            ['enumField', 'multiEnumField'],
            $this->provider->getVirtualFields($className)
        );
    }

    public function testIsVirtualField()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertTrue(
            $this->provider->isVirtualField($className, 'enumField')
        );
        $this->assertTrue(
            $this->provider->isVirtualField($className, 'multiEnumField')
        );
        $this->assertFalse(
            $this->provider->isVirtualField($className, 'nonConfigurableField')
        );
    }

    public function testGetVirtualFieldQueryForEnum()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertEquals(
            [
                'select' => [
                    'expr'         => 'target.targetField',
                    'return_type'  => 'enum',
                    'filter_by_id' => true
                ],
                'join'   => [
                    'left' => [
                        [
                            'join'  => 'entity.enumField',
                            'alias' => 'target'
                        ]
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($className, 'enumField')
        );
    }

    public function testGetVirtualFieldQueryForMultiEnum()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertEquals(
            [
                'select' => [
                    'expr'         => 'entity.' . ExtendHelper::getMultiEnumSnapshotFieldName('multiEnumField'),
                    'return_type'  => 'multiEnum',
                    'filter_by_id' => true
                ]
            ],
            $this->provider->getVirtualFieldQuery($className, 'multiEnumField')
        );
    }

    protected function initialize($className)
    {
        $metadata = $this->getClassMetadata(['enumField', 'multiEnumField', 'nonConfigurableField']);

        $enumFieldConfig = new Config(new FieldConfigId('extend', $className, 'enumField', 'enum'));
        $enumFieldConfig->set('target_field', 'targetField');
        $multiEnumFieldConfig = new Config(new FieldConfigId('extend', $className, 'multiEnumField', 'multiEnum'));

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->will($this->returnValue($metadata));

        $this->extendConfigProvider->expects($this->exactly(3))
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$className, 'enumField', true],
                        [$className, 'multiEnumField', true],
                        [$className, 'nonConfigurableField', false],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$className, 'enumField', $enumFieldConfig],
                        [$className, 'multiEnumField', $multiEnumFieldConfig],
                    ]
                )
            );
    }

    /**
     * @param string[] $associationNames
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getClassMetadata($associationNames = [])
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getAssociationNames')
            ->will($this->returnValue($associationNames));

        return $metadata;
    }
}
