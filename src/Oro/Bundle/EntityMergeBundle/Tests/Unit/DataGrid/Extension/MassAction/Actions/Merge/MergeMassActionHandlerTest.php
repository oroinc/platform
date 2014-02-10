<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DataGrid\Extension\MassAction\Actions\Merge;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge\MergeMassActionHandler;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use PHPUnit_Framework_MockObject_MockObject;

class MergeMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeMassActionHandler $target
     */
    private $target;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeArgs
     */
    private $fakeArgs;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $doctrineRegistry
     */
    private $doctrineRegistry;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeOptions
     */
    private $fakeOptions;

    private $fakeOptionsArray;

    private $fakeMetadata;

    private $fakeRepository;

    public function setUp()
    {
        $this->target = new MergeMassActionHandler();
        $fakeEntityManager = $this->getMock('EntityManager');

        $this->fakeMetadata = $this->getMock('\Doctrine\ORM\Mapping\ClassMetadata', array(), array(), '', false);
        $this->fakeRepository = $this->getMock('\Doctrine\ORM\EntityRepository', array(), array(), '', false);
        $this->doctrineRegistry = $this->getMock(
            '\Doctrine\Bundle\DoctrineBundle\Registry',
            array(),
            array(),
            '',
            false
        );
        $fakeEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->withAnyParameters()
            ->will($this->returnValue($this->fakeMetadata));
        $fakeEntityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->will($this->returnValue($this->fakeRepository));

        $this->doctrineRegistry
            ->expects($this->any())
            ->method('getManager')
            ->withAnyParameters()
            ->will($this->returnValue($fakeEntityManager));

        $this->target->setDoctrineRegistry($this->doctrineRegistry);

        $this->fakeArgs = $this->getMock(
            '\Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs',
            array(),
            array(),
            '',
            false
        );

        $this->fakeOptions = $this->getMock(
            'Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration',
            array(),
            array(),
            '',
            false
        );

        $fakeMassAction = $this->getMock(
            'Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface',
            array(),
            array(),
            '',
            false
        );
        $options = & $this->fakeOptionsArray;
        $this->fakeOptions
            ->expects($this->any())
            ->method('toArray')
            ->withAnyParameters()
            ->will($this->returnCallback(function () use (&$options) {
                return $options;
            }));
        $this->setCorrectOptions();
        $fakeMassAction->expects($this->any())->method('getOptions')->will($this->returnValue($this->fakeOptions));

        $this->fakeArgs
            ->expects($this->any())
            ->method('getMassAction')
            ->withAnyParameters()
            ->will($this->returnValue($fakeMassAction));
    }

    protected function setCorrectOptions()
    {

    }

    public function testMethodDoesNotThrowAnExceptionIfAllDataIsCorrect()
    {
        $this->target->handle($this->fakeArgs);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity name is missing
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfEntityNameIsEmpty()
    {
        $this->fakeOptionsArray['entity_name'] = '';

        $this->target->handle($this->fakeArgs);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity name is missing
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfMaxElementCountIsEmpty()
    {
        $this->fakeOptionsArray = array('entity_name' => 'test_entity', 'max_element_count' => '');

        $this->target->handle($this->fakeArgs);
    }
}
