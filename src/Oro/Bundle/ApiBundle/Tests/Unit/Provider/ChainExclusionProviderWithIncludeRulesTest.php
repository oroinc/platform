<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Provider\ChainExclusionProvider;

class ChainExclusionProviderWithIncludeRulesTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainExclusionProvider */
    protected $provider;

    public function setUp()
    {
        $hierarchyProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface');
        $hierarchyProvider->expects($this->any())
            ->method('getHierarchyForClassName')
            ->willReturnMap(
                [
                    ['Test\Entity\Entity1', ['Test\Entity\BaseEntity1']],
                    ['Test\Entity\Entity2', []],
                    ['Test\Entity\Entity3', []],
                ]
            );

        $this->provider = new ChainExclusionProvider(
            $hierarchyProvider,
            [
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity\BaseEntity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity\Entity3'],
            ]
        );

        $childProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface')
            ->setMockClassName('LowPriorityExclusionProvider')
            ->getMock();
        $childProvider->expects($this->any())
            ->method('isIgnoredEntity')
            ->willReturn(true);
        $childProvider->expects($this->any())
            ->method('isIgnoredField')
            ->willReturn(true);
        $childProvider->expects($this->any())
            ->method('isIgnoredRelation')
            ->willReturn(true);

        $this->provider->addProvider($childProvider);
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

    public function entityProvider()
    {
        return [
            ['Test\Entity\Entity1', true],
            ['Test\Entity\Entity3', false],
        ];
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

    public function fieldProvider()
    {
        $entity1 = $this->getEntityMetadata('Test\Entity\Entity1');
        $entity2 = $this->getEntityMetadata('Test\Entity\Entity2');
        $entity3 = $this->getEntityMetadata('Test\Entity\Entity3');

        return [
            [$entity1, 'field1', false],
            [$entity1, 'field2', false],
            [$entity1, 'field3', true],
            [$entity2, 'field1', true],
            [$entity3, 'field1', false],
        ];
    }

    /**
     * @param string $className
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntityMetadata($className)
    {
        return new ClassMetadata($className);
    }
}
