<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityBundle\Helper\DictionaryHelper;

class DictionaryHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var  DictionaryHelper */
    protected $dictionaryHelper;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $classMetadataMock;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadataMock;

    protected function setUp()
    {
        $this->dictionaryHelper = new DictionaryHelper();

        $this->classMetadataMock = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadataMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $identifierFieldNames
     * @param string $expected
     * @param boolean $exception
     *
     * @dataProvider getIdentifierFieldNamesDataProvider
     */
    public function testGetNamePrimaryKeyField($identifierFieldNames, $expected, $exception = false)
    {
        if ($exception) {
            $this->setExpectedException('Oro\Bundle\EntityBundle\Exception\RuntimeException');
        }

        $this->classMetadataMock
            ->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn($identifierFieldNames);

        $this->assertEquals(
            $expected,
            $this->dictionaryHelper->getNamePrimaryKeyField($this->classMetadataMock)
        );
    }

    /**
     * @return array
     */
    public function getIdentifierFieldNamesDataProvider()
    {
        return array(
            "no field names"           => [
                'identifierFieldNames' => [],
                'expected'             => null,
                'exception'            => true
            ],
            "more than one field name" => [
                'identifierFieldNames' => ['name', 'title'],
                'expected'             => null,
                'exception'            => true
            ],
            "one field name"           => [
                'identifierFieldNames' => ['name'],
                'expected'             => 'name'
            ]
        );
    }

    /**
     * @param array $fieldNames
     * @param array $searchFields
     * @param array $expected
     * @param boolean $exception
     *
     * @dataProvider getSearchFieldsDataProvider
     */
    public function testGetSearchFields($fieldNames, $searchFields, $expected, $exception = false)
    {
        if ($exception) {
            $this->setExpectedException('\LogicException');
        }

        $this->classMetadataMock
            ->method("getFieldNames")
            ->willReturn($fieldNames);

        $this->entityMetadataMock->defaultValues = array(
            'dictionary' => array(
                'search_fields' => $searchFields
            )
        );

        $this->assertEquals(
            $expected,
            $this->dictionaryHelper->getSearchFields($this->classMetadataMock, $this->entityMetadataMock)
        );
    }

    /**
     * @return array
     */
    public function getSearchFieldsDataProvider()
    {
        return [
            'search fields not empty #1'               => [
                'fieldNames'   => [],
                'searchFields' => ['title'],
                'expected'     => ['title']
            ],
            'search field exist in field names'        => [
                'fieldNames'   => ['name'],
                'searchFields' => ['name'],
                'expected'     => ['name']
            ],
            'search field is not exist in field names' => [
                'fieldNames'   => ['title'],
                'searchFields' => ['name'],
                'expected'     => ['name']
            ],
            'use default search field'                 => [
                'fieldNames'   => [DictionaryHelper::DEFAULT_SEARCH_FIELD],
                'searchFields' => [],
                'expected'     => [DictionaryHelper::DEFAULT_SEARCH_FIELD]
            ],
            'search fields property is not exist'      => [
                'fieldNames'   => [],
                'searchFields' => null,
                'expected'     => [],
                'exception'    => true
            ],
            'search fields are absent'                 => [
                'fieldNames'   => [],
                'searchFields' => [],
                'expected'     => [],
                'exception'    => true
            ]
        ];
    }

    /**
     * @param array $fieldNames
     * @param array $representationField
     * @param mixed $expected
     *
     * @dataProvider getRepresentationFieldDataProvider
     */
    public function testGetRepresentationField($fieldNames, $representationField, $expected)
    {
        $this->classMetadataMock
            ->method("getFieldNames")
            ->willReturn($fieldNames);

        $this->entityMetadataMock->defaultValues = array(
            'dictionary' => array(
                'representation_field' => $representationField
            )
        );

        $this->assertEquals(
            $expected,
            $this->dictionaryHelper->getRepresentationField($this->classMetadataMock, $this->entityMetadataMock)
        );
    }

    /**
     * @return array
     */
    public function getRepresentationFieldDataProvider()
    {
        return [
            'representation field is not defined'              => [
                'fieldNames'          => [],
                'representationField' => null,
                'expected'            => null
            ],
            'fields list is empty'                             => [
                'fieldNames'          => [],
                'representationField' => 'title',
                'expected'            => null
            ],
            'representation field is not exist in fields list' => [
                'fieldNames'          => ['name_test'],
                'representationField' => 'title',
                'expected'            => null
            ],
            'representation field is exist in fields list'     => [
                'fieldNames'          => ['title'],
                'representationField' => 'title',
                'expected'            => 'title'
            ]
        ];
    }
}
