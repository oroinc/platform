<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

class ConfigExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigExclusionProvider */
    private $provider;

    public function setUp()
    {
        $hierarchyProvider = $this->createMock(EntityHierarchyProviderInterface::class);
        $hierarchyProvider->expects(self::any())
            ->method('getHierarchyForClassName')
            ->willReturnMap([
                ['Test\Entity\Entity1', ['Test\Entity\BaseEntity1']],
                ['Test\Entity\Entity2', []],
                ['Test\Entity\Entity3', []],
                ['Test\Entity\Entity4', []]
            ]);

        $this->provider = new ConfigExclusionProvider(
            $hierarchyProvider,
            [
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity\BaseEntity1', 'field' => 'field2'],
                ['type' => 'date'],
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

    public function testIsIgnoredFieldByDataType()
    {
        $metadata = $this->getEntityMetadata(
            'Test\Entity\Entity2',
            [
                'field1' => 'date'
            ]
        );

        self::assertTrue(
            $this->provider->isIgnoredField($metadata, 'field1')
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

    public function entityProvider()
    {
        return [
            'excluded'                                    => ['Test\Entity\Entity3', true],
            'not excluded, has excluded fields'           => ['Test\Entity\Entity1', false],
            'not excluded, does not have excluded fields' => ['Test\Entity\Entity4', false]
        ];
    }

    public function fieldProvider()
    {
        return [
            'excluded'                                               => [
                $this->getEntityMetadata(
                    'Test\Entity\Entity1',
                    [
                        'field1' => 'integer',
                        'field2' => 'string',
                        'field3' => 'date'
                    ]
                ),
                'field1',
                true
            ],
            'excluded in parent class'                               => [
                $this->getEntityMetadata(
                    'Test\Entity\Entity1',
                    [
                        'field1' => 'integer',
                        'field2' => 'string',
                        'field3' => 'date'
                    ]
                ),
                'field2',
                true
            ],
            'not excluded, but entity has other excluded fields'     => [
                $this->getEntityMetadata(
                    'Test\Entity\Entity1',
                    [
                        'field1' => 'integer',
                        'field2' => 'string',
                        'field3' => 'date',
                        'field4' => 'text'
                    ]
                ),
                'field4',
                false
            ],
            'not excluded, entity does not have any excluded fields' => [
                $this->getEntityMetadata(
                    'Test\Entity\Entity2',
                    [
                        'field1' => 'integer',
                        'field2' => 'date'
                    ]
                ),
                'field1',
                false
            ],
            'excluded, because whole entity is excluded'             => [
                $this->getEntityMetadata(
                    'Test\Entity\Entity3',
                    [
                        'field1' => 'integer'
                    ]
                ),
                'field1',
                true
            ]
        ];
    }

    /**
     * @param string $className
     * @param array  $fields
     *
     * @return ClassMetadata
     */
    private function getEntityMetadata(string $className, array $fields = []): ClassMetadata
    {
        $metadata = new ClassMetadata($className);

        foreach ($fields as $fieldName => $fieldType) {
            $metadata->mapField(['fieldName' => $fieldName, 'type' => $fieldType]);
        }

        return $metadata;
    }
}
