<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class EntityWithFieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityClassResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $doctrine;

    /** @var EntityWithFieldsProvider */
    private $provider;

    /** @var EntityProvider */
    private $entityProvider;

    /** @var EntityFieldProvider */
    private $fieldProvider;

    protected function setUp()
    {
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver  = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will(
                $this->returnCallback(
                    function ($entityName) {
                        return str_replace(':', '\\Entity\\', $entityName);
                    }
                )
            );

        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->entityProvider = $this->getMock(
            'Oro\Bundle\EntityBundle\Provider\EntityProvider',
            ['getEntities'],
            [
                $this->entityConfigProvider,
                $this->extendConfigProvider,
                $this->entityClassResolver,
                $translator
            ]
        );

        $this->doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $entityHierarchyProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldProvider = new EntityFieldProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            $this->doctrine,
            $translator,
            $entityHierarchyProvider,
            [],
            []
        );
        $this->fieldProvider->setEntityProvider($this->entityProvider);

        $this->provider = new EntityWithFieldsProvider($this->fieldProvider, $this->entityProvider);
    }

    public function testGetFieldsWithRelations()
    {
        $this->prepareWithRelations();

        $entities = [
            [
                'name' => 'Acme\Entity\Test',
            ]
        ];

        $this->entityProvider->expects($this->once())
            ->method('getEntities')
            ->will($this->returnValue($entities));


        $result   = $this->provider->getFields();
        $expected = [
            'Acme\Entity\Test' => [
                'name'   => 'Acme\Entity\Test',
                'fields' => [
                    [
                        'name'  => 'field2',
                        'type'  => 'string',
                        'label' => 'B',
                    ],
                    [
                        'name'  => 'field1',
                        'type'  => 'integer',
                        'label' => 'C',
                        'identifier' => true,
                    ],
                    [
                        'name'                => 'rel1',
                        'type'                => 'integer',
                        'label'               => 'Rel1',
                        'relation_type'       => 'ref-many',
                        'related_entity_name' => 'Acme\Entity\Test1',
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepare($config)
    {
        $metadata = [];
        foreach ($config as $entityClassName => $entityData) {
            $entityMetadata             = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
                ->disableOriginalConstructor()
                ->getMock();
            $metadata[$entityClassName] = $entityMetadata;

            $fieldNames       = [];
            $fieldTypes       = [];
            $fieldIdentifiers = [];
            foreach ($entityData['fields'] as $fieldName => $fieldData) {
                $fieldNames[]       = $fieldName;
                $fieldTypes[]       = [$fieldName, $fieldData['type']];
                $fieldIdentifiers[] = [$fieldName, isset($fieldData['identifier']) ? $fieldData['identifier'] : false];
            }
            $entityMetadata->expects($this->any())
                ->method('getFieldNames')
                ->will($this->returnValue($fieldNames));
            $entityMetadata->expects($this->any())
                ->method('isIdentifier')
                ->will($this->returnValueMap($fieldIdentifiers));
            $entityMetadata->expects($this->any())
                ->method('getAssociationMappings')
                ->will(
                    $this->returnValue(
                        [
                            [
                                'targetEntity' => 'Acme\Entity\Test1',
                                'inversedBy'   => null,
                                'sourceEntity' => 'Acme\Entity\Test22',
                                'fieldName'    => 'Test22field1',
                                'type'         => 2
                            ]
                        ]
                    )
                );


            if (isset($entityData['relations'])) {
                $relNames         = [];
                $relTargetClasses = [];
                foreach ($entityData['relations'] as $relName => $relData) {
                    $fieldTypes[]       = [$relName, $relData['type']];
                    $relNames[]         = $relName;
                    $relTargetClasses[] = [$relName, $relData['target_class']];
                }
                $entityMetadata->expects($this->any())
                    ->method('getAssociationNames')
                    ->will($this->returnValue($relNames));
                $entityMetadata->expects($this->any())
                    ->method('getAssociationTargetClass')
                    ->will($this->returnValueMap($relTargetClasses));
                $entityMetadata->expects($this->any())
                    ->method('getAssociationMappedByTargetField')
                    ->will($this->returnValue('id'));
            }
            $entityMetadata->expects($this->any())
                ->method('getTypeOfField')
                ->will($this->returnValueMap($fieldTypes));
        }

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will(
                $this->returnCallback(
                    function ($entityClassName) use (&$metadata) {
                        return $metadata[$entityClassName];
                    }
                )
            );
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        $this->entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use (&$config) {
                        if (isset($config[$className])) {
                            if ($fieldName === null) {
                                return true;
                            }
                            if (isset($config[$className]['fields'][$fieldName]['config'])) {
                                return true;
                            }
                            if (isset($config[$className]['relations'][$fieldName]['config'])) {
                                return true;
                            }
                        }

                        return false;
                    }
                )
            );
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use (&$config) {
                        if (isset($config[$className])) {
                            if ($fieldName === null) {
                                return $this->getEntityConfig($className, $config[$className]['config']);
                            }
                            if (isset($config[$className]['fields'][$fieldName]['config'])) {
                                return $this->getEntityFieldConfig(
                                    $className,
                                    $fieldName,
                                    $config[$className]['fields'][$fieldName]['type'],
                                    $config[$className]['fields'][$fieldName]['config']
                                );
                            }
                            if (isset($config[$className]['relations'][$fieldName]['config'])) {
                                return $this->getEntityFieldConfig(
                                    $className,
                                    $fieldName,
                                    $config[$className]['relations'][$fieldName]['type'],
                                    $config[$className]['relations'][$fieldName]['config']
                                );
                            }
                        }

                        return null;
                    }
                )
            );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepareWithRelations()
    {
        $config = [
            'Acme\Entity\Test'    => [
                'config'    => [
                    'label'        => 'Test Label',
                    'plural_label' => 'Test Plural Label',
                    'icon'         => 'icon-test',
                ],
                'fields'    => [
                    'field1' => [
                        'type'       => 'integer',
                        'identifier' => true,
                        'config'     => [
                            'label' => 'C',
                        ]
                    ],
                    'field2' => [
                        'type'   => 'string',
                        'config' => [
                            'label' => 'B',
                        ]
                    ],
                ],
                'relations' => [
                    'rel1' => [
                        'target_class' => 'Acme\Entity\Test1',
                        'type'         => 'ref-many',
                        'config'       => [
                            'label' => 'Rel1',
                        ]
                    ],
                ]
            ],
            'Acme\Entity\Test1'   => [
                'config'    => [
                    'label'        => 'Test1 Label',
                    'plural_label' => 'Test1 Plural Label',
                    'icon'         => 'icon-test1',
                ],
                'fields'    => [
                    'id'          => [
                        'type'       => 'integer',
                        'identifier' => true,
                        'config'     => [
                            'label' => 'B',
                        ]
                    ],
                    'Test1field2' => [
                        'type'   => 'string',
                        'config' => [
                            'label' => 'A',
                        ]
                    ],
                ],
            ],
        ];
        $this->prepare($config);
    }

    protected function getEntityConfig($entityClassName, $values)
    {
        $entityConfigId = new EntityConfigId('entity', $entityClassName);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }

    protected function getEntityFieldConfig($entityClassName, $fieldName, $fieldType, $values)
    {
        $entityFieldConfigId = new FieldConfigId('entity', $entityClassName, $fieldName, $fieldType);
        $entityFieldConfig   = new Config($entityFieldConfigId);
        $entityFieldConfig->setValues($values);

        return $entityFieldConfig;
    }

    protected function getEntityIds($entityClassName, $config)
    {
        $result = [];
        foreach ($config[$entityClassName]['fields'] as $fieldName => $fieldConfig) {
            $result[] = new FieldConfigId('entity', $entityClassName, $fieldName, $fieldConfig['type']);
        }

        return $result;
    }
}
