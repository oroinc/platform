<?php
/**
 * Created by PhpStorm.
 * User: Alexandr
 * Date: 2/11/14
 * Time: 5:26 PM
 */

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;


use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\Metadata;
use OroCRM\Bundle\AccountBundle\Entity\Account;

class EntityDataFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EntityDataFactory $target
     */
    private $target;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeMetadataFactory
     */
    private $fakeMetadataFactory;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeEntityProvider
     */
    private $fakeEntityProvider;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeEntityProvider
     */
    private $fakeMetadata;

    /**
     * @var array
     */
    private $fakeEntities;

    private $fakeFieldsMetadata;

    public function setUp()
    {
        $this->fakeMetadataFactory = $this->getMock(
            '\Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory',
            array(),
            array(),
            '',
            false
        );
        $this->fakeEntityProvider = $this->getMock(
            '\Oro\Bundle\EntityMergeBundle\Data\EntityProvider',
            array(),
            array(),
            '',
            false
        );
        $this->fakeMetadata = $this->getMock(
            '\Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata',
            array(),
            array(),
            '',
            false
        );
        $this->fakeMetadata->expects($this->any())->method('getClassName')->will(
            $this->returnValue('\OroCRM\Bundle\AccountBundle\Entity\Account')
        );

        $fakeFieldsMetadata = & $this->fakeFieldsMetadata;
        $fakeFieldsMetadata = array();
        $this->fakeMetadata->expects($this->any())->method('getFieldsMetadata')->will(
            $this->returnCallback(
                function () use (&$fakeFieldsMetadata) {
                    return $fakeFieldsMetadata;
                }
            )
        );
        $this->fakeMetadataFactory->expects($this->any())->method('createMergeMetadata')->with(
            '\OroCRM\Bundle\AccountBundle\Entity\Account'
        )->will($this->returnValue($this->fakeMetadata));
        $this->fakeEntities = array(new Account(), new Account());
        $this->target = new EntityDataFactory($this->fakeMetadataFactory, $this->fakeEntityProvider);
    }

    public function testCreateEntityDataShouldReturnCorrectEntities()
    {
        $result = $this->target->createEntityData('\OroCRM\Bundle\AccountBundle\Entity\Account', $this->fakeEntities);
        $this->assertEquals($result->getClassName(), '\OroCRM\Bundle\AccountBundle\Entity\Account');
        $this->assertEquals($this->fakeMetadata, $result->getMetadata());
        $expected = $this->fakeEntities;
        $this->assertEquals($result->getEntities(), $expected);
    }

    public function testCreateEntityDataShouldSetFieldsMetadataForEachField()
    {
        $options = array();
        $doctrineMetadata = $this->getMock(
            '\Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata',
            array(),
            array(),
            '',
            false
        );
        $doctrineMetadata->expects($this->any())->method('has')->will($this->returnValue(true));
        $doctrineMetadata->expects($this->at(1))->method('get')->will($this->returnValue('testFieldName'));
        $doctrineMetadata->expects($this->at(3))->method('get')->will($this->returnValue('SecondFieldTest'));
        $this->fakeFieldsMetadata = array(
            new FieldMetadata($options, $doctrineMetadata),
            new FieldMetadata($options, $doctrineMetadata)
        );
        $result = $this->target->createEntityData('\OroCRM\Bundle\AccountBundle\Entity\Account', $this->fakeEntities);

        $field = $result->getFields();
        $this->assertArrayHasKey('testFieldName', $field);
        $this->assertArrayHasKey('SecondFieldTest', $field);
    }

    public function testCreateEntityDataByIdsShouldCallCreateEntityDataWithCorrectData()
    {
        $this->fakeEntityProvider->expects($this->any())->method('getEntitiesByIds')->with(
            $this->equalTo('\OroCRM\Bundle\AccountBundle\Entity\Account'),
            $this->callback(
                function ($params) {
                    return $params[0] == '12' && $params[1] == '88';
                }
            )
        )->will($this->returnValue($this->fakeEntities));
        $expected = $this->fakeEntities;
        $result = $this->target->createEntityDataByIds(
            '\OroCRM\Bundle\AccountBundle\Entity\Account',
            array('12', '88')
        );
        $this->assertEquals($result->getEntities(), $expected);
    }
}
