<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

class ConfigExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigExclusionProvider */
    private $provider;

    public function setUp()
    {
        $hierarchyProvider = $this->createMock(EntityHierarchyProviderInterface::class);
        $hierarchyProvider->expects(self::any())
            ->method('getHierarchyForClassName')
            ->willReturnMap(
                [
                    ['Test\Entity\Entity1', ['Test\Entity\BaseEntity1']],
                    ['Test\Entity\Entity2', []],
                    ['Test\Entity\Entity3', []]
                ]
            );

        $this->provider = new ConfigExclusionProvider(
            $hierarchyProvider,
            [
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field3'],
                ['entity' => 'Test\Entity\Entity2'],
                ['entity' => 'Test\Entity\Entity3']
            ],
            [
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity\BaseEntity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity\Entity3']
            ]
        );
    }

    /**
     * @dataProvider entityProvider
     */
    public function testIsIgnoredEntity($className, $expected)
    {
        self::assertEquals(
            $expected,
            $this->provider->isIgnoredEntity($className)
        );
    }

    public function entityProvider()
    {
        return [
            ['Test\Entity\Entity1', false],
            ['Test\Entity\Entity2', true],
            ['Test\Entity\Entity3', false]
        ];
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredField($metadata, $fieldName, $expected)
    {
        self::assertEquals(
            $expected,
            $this->provider->isIgnoredField($metadata, $fieldName)
        );
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredRelation($metadata, $associationName, $expected)
    {
        self::assertEquals(
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
            [$entity3, 'field1', false]
        ];
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function getEntityMetadata($className)
    {
        return new ClassMetadata($className);
    }
}
