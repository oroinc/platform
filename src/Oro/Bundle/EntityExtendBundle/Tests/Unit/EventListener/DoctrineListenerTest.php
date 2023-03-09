<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\MappingException;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EventListener\DoctrineListener;
use Oro\Bundle\EntityExtendBundle\ORM\ExtendMetadataBuilder;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class DoctrineListenerTest extends OrmTestCase
{
    /** @var ExtendMetadataBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataBuilder;

    /** @var AnnotationReader */
    private $reader;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var DoctrineListener */
    private $listener;

    protected function setUp(): void
    {
        $this->reader = new AnnotationReader();
        $this->metadataBuilder = $this->createMock(ExtendMetadataBuilder::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);

        $this->listener = new DoctrineListener(
            $this->metadataBuilder,
            $this->reader,
            $this->extendConfigProvider
        );
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testProcessFieldMappings(
        string $path,
        array $expectedValues,
        string $expectedException = null
    ) {
        if ($expectedException) {
            return;
        }

        $this->metadataBuilder->expects($this->any())
            ->method('supports')
            ->willReturn(false);
        $this->extendConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(function ($className, $fieldName) {
                return new Config(
                    new FieldConfigId('extend', $className, $fieldName),
                    ['default' => true]
                );
            });

        $em = $this->getTestEntityManager();
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, $this->listener);
        $em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver($this->reader, $path));

        foreach (array_keys($expectedValues) as $entityName) {
            $classMetadata = $em->getClassMetadata($entityName);

            foreach ($classMetadata->fieldMappings as $fieldMapping) {
                if (isset($fieldMapping['id'])) {
                    continue;
                }

                $this->assertArrayHasKey('default', $fieldMapping);
                $this->assertTrue($fieldMapping['default']);
            }
        }
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testProcessDiscriminatorValues(
        string $path,
        array $expectedValues,
        string $expectedException = null
    ) {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $this->metadataBuilder->expects($this->any())
            ->method('supports')
            ->willReturn(false);

        $em = $this->getTestEntityManager();
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, $this->listener);
        $em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver($this->reader, $path));

        foreach ($expectedValues as $entityName => $data) {
            [$value, $map] = $data;

            $class = $em->getClassMetadata($entityName);
            $this->assertSame($map, $class->discriminatorMap);
            $this->assertSame($value, $class->discriminatorValue);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function entitiesProvider(): array
    {
        $dirPath = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
        $prefix = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\\';

        return [
            'regular entities, empty map expected' => [
                'path' => $dirPath . 'Regular',
                'expectedValues' => [],
            ],
            'regular entities, should not read values, empty map expected' => [
                'path' => $dirPath . 'RegularWithAnnotation',
                'expectedValues' => [
                    $prefix . 'RegularWithAnnotation\TestComment' => [null, []]
                ],
            ],
            'inherited entities, should work if map set on parent level' => [
                'path' => $dirPath . 'InheritedWithMapOnParent',
                'expectedValues' => [
                    $prefix . 'InheritedWithMapOnParent\BaseEntity' => [
                        'base',
                        [
                            'base' => $prefix . 'InheritedWithMapOnParent\BaseEntity',
                            'child' => $prefix . 'InheritedWithMapOnParent\ChildEntity'
                        ]

                    ],
                    $prefix . 'InheritedWithMapOnParent\ChildEntity' => [
                        'child',
                        [
                            'base' => $prefix . 'InheritedWithMapOnParent\BaseEntity',
                            'child' => $prefix . 'InheritedWithMapOnParent\ChildEntity'
                        ]
                    ]
                ]
            ],
            'inherited entities, should work if map auto generated' => [
                'path' => $dirPath . 'InheritedWithAutogeneratedMap',
                'expectedValues' => [
                    $prefix . 'InheritedWithAutogeneratedMap\BaseEntity' => [
                        'baseentity',
                        [
                            'baseentity' => $prefix . 'InheritedWithAutogeneratedMap\BaseEntity',
                            'childentity' => $prefix . 'InheritedWithAutogeneratedMap\ChildEntity'
                        ]

                    ],
                    $prefix . 'InheritedWithAutogeneratedMap\ChildEntity' => [
                        'childentity',
                        [
                            'baseentity' => $prefix . 'InheritedWithAutogeneratedMap\BaseEntity',
                            'childentity' => $prefix . 'InheritedWithAutogeneratedMap\ChildEntity'
                        ]
                    ]
                ]
            ],
            'inherited entities, should collect values set on child level' => [
                'path' => $dirPath . 'InheritedWithValues',
                'expectedValues' => [
                    $prefix . 'InheritedWithValues\BaseEntity' => [
                        'base',
                        [
                            'base' => $prefix . 'InheritedWithValues\BaseEntity',
                            'child' => $prefix . 'InheritedWithValues\ChildEntity'
                        ]

                    ],
                    $prefix . 'InheritedWithValues\ChildEntity' => [
                        'child',
                        [
                            'base' => $prefix . 'InheritedWithValues\BaseEntity',
                            'child' => $prefix . 'InheritedWithValues\ChildEntity'
                        ]
                    ]
                ]
            ],
            'inherited entities, should not break things with MS in the middle of hierarchy' => [
                'path' => $dirPath . 'InheritedWithMSInTheMiddle',
                'expectedValues' => [
                    $prefix . 'InheritedWithMSInTheMiddle\BaseEntity' => [
                        'base',
                        [
                            'base' => $prefix . 'InheritedWithMSInTheMiddle\BaseEntity',
                            'child' => $prefix . 'InheritedWithMSInTheMiddle\ChildEntity'
                        ]

                    ],
                    $prefix . 'InheritedWithMSInTheMiddle\ChildEntity' => [
                        'child',
                        [
                            'base' => $prefix . 'InheritedWithMSInTheMiddle\BaseEntity',
                            'child' => $prefix . 'InheritedWithMSInTheMiddle\ChildEntity'
                        ]
                    ],
                ]
            ],
            'inherited entities, should raise duplicate exception' => [
                'path' => $dirPath . 'InheritedWithValuesDuplicate',
                'expectedValues' => [
                    $prefix . 'InheritedWithValuesDuplicate\BaseEntity' => [null, null]
                ],
                'expectedException' => MappingException::class
            ],
        ];
    }
}
