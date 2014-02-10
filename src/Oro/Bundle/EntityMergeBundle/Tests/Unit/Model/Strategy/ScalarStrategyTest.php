<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Model\Strategy\ScalarStrategy;

class ScalarStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScalarStrategy $strategy
     */
    protected $strategy;

    public function setUp()
    {
        $this->strategy = new ScalarStrategy();
    }

    public function testSupports()
    {
        $fieldData        = $this->createFieldData();
        $fieldMetadata    = $this->createFieldMetadata();
        $doctrineMetadata = $this->createFieldMetadata();

        $doctrineMetadata
            ->expects($this->any())
            ->method('get')
            ->with('type')
            ->will($this->returnValue('string'));

        $fieldMetadata
            ->expects($this->once())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadata));

        $this->assertFalse($this->strategy->supports($fieldData));
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->setMethods(['getMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->setMethods(['getMetadata', 'get', 'getDoctrineMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
