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
    protected $databaseHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

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

        $this->fieldHelper = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->databaseHelper = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\DatabaseHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->strategy = new ConfigurableAddOrReplaceStrategy(
            $this->importStrategyHelper,
            $this->fieldHelper,
            $this->databaseHelper
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
     * @param string      $identifier
     * @param object|null $existingEntity
     * @param array       $objectValue
     * @param array       $itemData optional
     *
     * @dataProvider entityProvider
     */
    public function testProcess(
        $className,
        array $fields,
        $identifier,
        $existingEntity,
        array $objectValue,
        $itemData = null
    ) {
        $object = new $className;

        $this->context->expects($this->once())
            ->method('getValue')
            ->with('itemData')
            ->will($this->returnValue($itemData));

        if ($existingEntity) {
            $this->importStrategyHelper
                ->expects($this->once())
                ->method('importEntity')
                ->with(
                    $existingEntity,
                    $object,
                    $this->calcExcludedFields($fields, $identifier, $itemData)
                )
                ->will($this->returnSelf());
        } else {
            $this->importStrategyHelper
                ->expects($this->never())
                ->method('importEntity');
        }

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

        $this->databaseHelper->expects($this->any())
            ->method('getIdentifierFieldName')
            ->with($className)
            ->will($this->returnValue($identifier));

        if (!empty($objectValue)) {
            $this->fieldHelper
                ->expects($this->any())
                ->method('getObjectValue')
                ->with($this->equalTo($object))
                ->will($this->returnValue(reset($objectValue)));

            $this->databaseHelper->expects($this->any())
                ->method('findOneBy')
                ->with($className, $objectValue)
                ->will($this->returnValue($existingEntity));
        }

        $this->databaseHelper->expects($this->any())
            ->method('find')
            ->with($className, $identifier)
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

        return array(
            'empty' => array(
                'className'      => self::ENTITY,
                'fields'         => [],
                'identifier'     => null,
                'existingEntity' => null,
                'objectValue'    => []
            ),
            'not_existing' => array(
                'className'      => self::ENTITY,
                'fields'         => array(
                    'identity' => array(
                        'name'     => 'identity',
                        'excluded' => false,
                        'identity' => true,
                    ),
                    'excluded' => array(
                        'name'     => 'excluded',
                        'excluded' => true,
                        'identity' => false,
                    ),
                ),
                'identifier'     => 'id',
                'existingEntity' => null,
                'objectValue'    => array(
                    'identity' => 'value'
                )
            ),
            'existing and full fields import' => array(
                'className' => self::ENTITY,
                'fields'    => array(
                    'identity' => array(
                        'name'     => 'identity',
                        'excluded' => false,
                        'identity' => true,
                    ),
                    'excluded' => array(
                        'name'     => 'excluded',
                        'excluded' => true,
                        'identity' => false,
                    ),
                ),
                'identifier'     => 'id',
                'existingEntity' => $object,
                'objectValue'    => array(
                    'identity' => 'value'
                )
            ),
            'existing and several fields import' => array(
                'className' => self::ENTITY,
                'fields'    => array(
                    'identity' => array(
                        'name'     => 'identity',
                        'excluded' => false,
                        'identity' => true,
                    ),
                    'imported' => array(
                        'name'     => 'imported',
                        'excluded' => false,
                        'identity' => false,
                    ),
                    'not-imported' => array(
                        'name'     => 'not-imported',
                        'excluded' => false,
                        'identity' => false,
                    ),
                ),
                'identifier'     => 'id',
                'existingEntity' => $object,
                'objectValue'    => array(
                    'identity' => 'value',
                ),
                'itemData' => array(
                    'identity' => 'value',
                    'imported' => 'value'
                )
            ),
        );
    }

    /**
     * @param string $className
     * @param array  $fields
     * @param string $identifier
     *
     * @dataProvider relationEntityProvider
     */
    public function testProcessRelation($className, array $fields, $identifier)
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
            ->expects($this->any())
            ->method('getItemData')
            ->will(
                $this->returnCallback(
                    function ($data, $fieldName) {
                        return !empty($data[$fieldName]) ? $data[$fieldName] : array();
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

        $this->databaseHelper->expects($this->any())
            ->method('getIdentifierFieldName')
            ->with($className)
            ->will($this->returnValue($identifier));

        $this->databaseHelper->expects($this->any())
            ->method('find')
            ->with($className, $identifier)
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
                'identifier' => 'id',
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
                'identifier' => 'id',
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

        $this->databaseHelper->expects($this->any())
            ->method('getIdentifierFieldName')
            ->with(self::ENTITY)
            ->will($this->returnValue('id'));

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

    /**
     * @param array  $fields
     * @param string $singleIdentifier
     * @param array|null  $itemData optional
     * @return array
     */
    protected function calcExcludedFields($fields, $singleIdentifier, array $itemData = null)
    {
        $excludedFields = array($singleIdentifier);

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($field['excluded']
                || ($itemData !== null && !array_key_exists($fieldName, $itemData))
                && !$field['identity']
            ) {
                $excludedFields[] = $fieldName;
            }
        }

        return $excludedFields;
    }
}
