<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub\ImportEntity;

class ConfigurableAddOrReplaceStrategyTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub\ImportEntity';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $importStrategyHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entity;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var ConfigurableAddOrReplaceStrategy
     */
    protected $strategy;

    protected function setUp()
    {
        $this->importStrategyHelper = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMock('Doctrine\ORM\EntityManagerInterface');

        $this->importStrategyHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($this->em));

        $this->fieldHelper = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->metadata = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new ConfigurableAddOrReplaceStrategy(
            $this->importStrategyHelper,
            $this->fieldHelper
        );
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Strategy must have import/export context
     */
    public function testAssertEnvironmentContext()
    {
        $this->strategy->process($this->getMock(self::ENTITY));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Strategy must know about entity name
     */
    public function testAssertEnvironmentEntityName()
    {
        $this->strategy->setImportExportContext($this->context);

        $this->strategy->process($this->getMock(self::ENTITY));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Imported entity must be instance of Namespace\Entity
     */
    public function testAssertEnvironmentEntityInstance()
    {
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName('Namespace\Entity');

        $this->strategy->process($this->getMock(self::ENTITY));
    }

    /**
     * @param string      $className
     * @param array       $fields
     * @param array       $identifier
     * @param object|null $existingEntity
     * @param array       $objectValue
     *
     * @dataProvider entityProvider
     */
    public function testProcess($className, array $fields, array $identifier, $existingEntity, array $objectValue)
    {
        $singleIdentifier = !empty($identifier[0]) ? $identifier[0] : null;
        $object           = new $className;

        $this->fieldHelper
            ->expects($this->atLeastOnce())
            ->method('getFields')
            ->will($this->returnValue($fields));

        $this->fieldHelper
            ->expects($this->any())
            ->method('getConfigValue')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName, $type, $default) use ($fields) {
                        $field = $fields[$fieldName];

                        return isset($field[$type]) ? $field[$type] : $default;
                    }
                )
            );

        $this->metadata
            ->expects($this->atLeastOnce())
            ->method('getIdentifierValues')
            ->will($this->returnValue($identifier));

        $this->em
            ->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->with($this->equalTo($className))
            ->will($this->returnValue($this->metadata));

        if (!empty($objectValue)) {
            $this->fieldHelper
                ->expects($this->any())
                ->method('getObjectValue')
                ->with($this->equalTo($object))
                ->will($this->returnValue(reset($objectValue)));

            $this->repository
                ->expects($this->any())
                ->method('findOneBy')
                ->with($this->equalTo($objectValue))
                ->will($this->returnValue($existingEntity));

            $this->em
                ->expects($this->any())
                ->method('getRepository')
                ->with($this->equalTo($className))
                ->will($this->returnValue($this->repository));
        }

        $this->em
            ->expects($this->any())
            ->method('find')
            ->with($this->equalTo($className), $this->equalTo($singleIdentifier))
            ->will($this->returnValue($existingEntity));

        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName($className);

        $this->strategy->process($object);
    }

    /**
     * @return array
     */
    public function entityProvider()
    {
        $object = new ImportEntity();

        return [
            'empty'        => [
                'className'      => self::ENTITY,
                'fields'         => [],
                'identifier'     => [],
                'existingEntity' => null,
                'objectValue'    => []
            ],
            'existing'     => [
                'className'      => self::ENTITY,
                'fields'         => [
                    'identity' => [
                        'name'     => 'identity',
                        'excluded' => false,
                        'identity' => true,
                    ],
                    'excluded' => [
                        'name'     => 'excluded',
                        'excluded' => true,
                        'identity' => false,
                    ],
                ],
                'identifier'     => ['id'],
                'existingEntity' => $object,
                'objectValue'    => [
                    'identity' => 'value'
                ]
            ],
            'not_existing' => [
                'className'      => self::ENTITY,
                'fields'         => [
                    'identity' => [
                        'name'     => 'identity',
                        'excluded' => false,
                        'identity' => true,
                    ],
                    'excluded' => [
                        'name'     => 'excluded',
                        'excluded' => true,
                        'identity' => false,
                    ],
                ],
                'identifier'     => ['id'],
                'existingEntity' => null,
                'objectValue'    => [
                    'identity' => 'value'
                ]
            ]
        ];
    }

    /**
     * @param string $className
     * @param array  $fields
     * @param array  $identifier
     *
     * @dataProvider relationEntityProvider
     */
    public function testProcessRelation($className, array $fields, array $identifier)
    {
        $object = new $className;

        $this->fieldHelper
            ->expects($this->atLeastOnce())
            ->method('getFields')
            ->will($this->returnValue($fields));

        $this->fieldHelper
            ->expects($this->atLeastOnce())
            ->method('isRelation')
            ->will($this->returnValue(true));

        $this->fieldHelper
            ->expects($this->any())
            ->method('getObjectValue')
            ->will(
                $this->returnCallback(
                    function ($entityClassName, $fieldName) use ($fields) {
                        $field = $fields[$fieldName];

                        return $field['value'];
                    }
                )
            );

        $this->fieldHelper
            ->expects($this->atLeastOnce())
            ->method('isSingleRelation')
            ->will(
                $this->returnCallback(
                    function ($field) {
                        return in_array(
                            $field['relation_type'],
                            [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE]
                        );
                    }
                )
            );

        $this->fieldHelper
            ->expects($this->any())
            ->method('isMultipleRelation')
            ->will(
                $this->returnCallback(
                    function ($field) {
                        return in_array(
                            $field['relation_type'],
                            [ClassMetadataInfo::MANY_TO_MANY, ClassMetadataInfo::ONE_TO_MANY]
                        );
                    }
                )
            );

        $this->metadata
            ->expects($this->atLeastOnce())
            ->method('getIdentifierValues')
            ->will($this->returnValue($identifier));

        $this->em
            ->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->with($this->equalTo($className))
            ->will($this->returnValue($this->metadata));

        $this->em
            ->expects($this->any())
            ->method('find')
            ->with($this->equalTo($className), $this->equalTo($identifier[0]))
            ->will($this->returnValue($object));

        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName($className);

        $this->strategy->process($object);
    }

    /**
     * @return array
     */
    public function relationEntityProvider()
    {
        $object = new ImportEntity();

        return [
            'single'   => [
                'className'  => self::ENTITY,
                'fields'     => [
                    'single' => [
                        'name'                => 'single',
                        'relation_type'       => ClassMetadataInfo::MANY_TO_ONE,
                        'related_entity_name' => self::ENTITY,
                        'value'               => $object
                    ],
                ],
                'identifier' => ['id'],
            ],
            'multiple' => [
                'className'  => self::ENTITY,
                'fields'     => [
                    'multiple' => [
                        'name'                => 'multiple',
                        'relation_type'       => ClassMetadataInfo::ONE_TO_MANY,
                        'related_entity_name' => self::ENTITY,
                        'value'               => new ArrayCollection([$object, $object])
                    ],
                ],
                'identifier' => ['id'],
            ],
        ];
    }

    public function testValidateAndUpdateContext()
    {
        $object = new ImportEntity();
        $errors = ['error'];

        $this->fieldHelper
            ->expects($this->atLeastOnce())
            ->method('getFields')
            ->will($this->returnValue([]));

        $this->metadata
            ->expects($this->atLeastOnce())
            ->method('getIdentifierValues')
            ->will($this->returnValue(['id']));

        $this->em
            ->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->with($this->equalTo(self::ENTITY))
            ->will($this->returnValue($this->metadata));

        $this->importStrategyHelper
            ->expects($this->once())
            ->method('validateEntity')
            ->with($this->equalTo($object))
            ->will($this->returnValue(['error']));

        $this->importStrategyHelper
            ->expects($this->once())
            ->method('addValidationErrors')
            ->with($this->equalTo($errors), $this->equalTo($this->context));

        $this->context
            ->expects($this->atLeastOnce())
            ->method('incrementErrorEntriesCount');

        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(self::ENTITY);

        $this->strategy->process($object);
    }
}
