<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ConfigExclusionProvider;

class ConfigExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigExclusionProvider */
    protected $provider;

    public function setUp()
    {
        $hierarchyProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $hierarchyProvider->expects($this->any())
            ->method('getHierarchyForClassName')
            ->will(
                $this->returnValueMap(
                    [
                        ['Test\Entity\Entity1', ['Test\Entity\BaseEntity1']],
                        ['Test\Entity\Entity2', []],
                        ['Test\Entity\Entity3', []],
                    ]
                )
            );

        $this->provider = new ConfigExclusionProvider(
            $hierarchyProvider,
            [
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity\BaseEntity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity\BaseEntity1', 'type' => 'float'],
                ['type' => 'date'],
                ['entity' => 'Test\Entity\Entity3'],
            ]
        );
    }

    /**
     * @dataProvider entityProvider
     */
    public function testIsIgnoredEntity($className, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->provider->isIgnoredEntity($className)
        );
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredField($metadata, $fieldName, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->provider->isIgnoredField($metadata, $fieldName)
        );
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredRelation($metadata, $associationName, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->provider->isIgnoredRelation($metadata, $associationName)
        );
    }

    public function entityProvider()
    {
        return [
            ['Test\Entity\Entity1', false],
            ['Test\Entity\Entity3', true],
        ];
    }

    public function fieldProvider()
    {
        return [
            [$this->getEntityMetadata(
                'Test\Entity\Entity1',
                [
                    'field1' => 'integer',
                    'field2' => 'string',
                    'field3' => 'date',
                    'field4' => 'text',
                    'field5' => 'date',
                    'field6' => 'float',
                ]
            ), 'field1', true],
            [$this->getEntityMetadata(
                'Test\Entity\Entity1',
                [
                    'field1' => 'integer',
                    'field2' => 'string',
                    'field3' => 'date',
                    'field4' => 'text',
                    'field5' => 'date',
                    'field6' => 'float',
                ]
            ), 'field2', true],
            [$this->getEntityMetadata(
                'Test\Entity\Entity1',
                [
                    'field1' => 'integer',
                    'field2' => 'string',
                    'field3' => 'date',
                    'field4' => 'text',
                    'field5' => 'date',
                    'field6' => 'float',
                ]
            ), 'field3', true],
            [$this->getEntityMetadata(
                'Test\Entity\Entity1',
                [
                    'field1' => 'integer',
                    'field2' => 'string',
                    'field3' => 'date',
                    'field4' => 'text',
                    'field5' => 'date',
                    'field6' => 'float',
                ]
            ), 'field4', false],
            [$this->getEntityMetadata(
                'Test\Entity\Entity1',
                [
                    'field1' => 'integer',
                    'field2' => 'string',
                    'field3' => 'date',
                    'field4' => 'text',
                    'field5' => 'date',
                    'field6' => 'float',
                ]
            ), 'field5', true],
            [$this->getEntityMetadata(
                'Test\Entity\Entity1',
                [
                    'field1' => 'integer',
                    'field2' => 'string',
                    'field3' => 'date',
                    'field4' => 'text',
                    'field5' => 'date',
                    'field6' => 'float',
                ]
            ), 'field6', true],
            [$this->getEntityMetadata(
                'Test\Entity\Entity2',
                [
                    'field1' => 'integer',
                    'field2' => 'date',
                ]
            ), 'field1', false],
            [$this->getEntityMetadata(
                'Test\Entity\Entity2',
                [
                    'field1' => 'integer',
                    'field2' => 'date',
                ]
            ), 'field2', true],
            [$this->getEntityMetadata(
                'Test\Entity\Entity3',
                [
                    'field1' => 'integer',
                ]
            ), 'field1', true],
        ];
    }

    protected function getEntityMetadata($className, $fields = [])
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($className));

        $fieldTypes = [];
        foreach ($fields as $fieldName => $fieldType) {
            $fieldTypes[] = [$fieldName, $fieldType];
        }

        if (empty($fieldTypes)) {
            $metadata->expects($this->never())
                ->method('getTypeOfField');
        } else {
            $metadata->expects($this->any())
                ->method('getTypeOfField')
                ->will($this->returnValueMap($fieldTypes));
        }

        return $metadata;
    }
}
