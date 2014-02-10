<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DataGrid\Extension\MassAction\Actions\Merge;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge\MergeMassActionHandler;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use OroCRM\Bundle\AccountBundle\Entity\Account;
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
    private $dataProvider;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeOptions
     */
    private $fakeOptions;

    private $fakeOptionsArray;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeResult
     */
    private $fakeResult;

    private $firstTestObject;

    private $secondTestObject;

    private $firstEntity;

    private $secondEntity;

    private function initMockObjects()
    {
        $this->dataProvider = $this->getMock(
            '\Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge\MergeEntitiesDataProvider',
            array(),
            array(),
            '',
            false
        );
        $this->fakeResult = $this->getMock(
            '\Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface',
            array(),
            array(),
            '',
            false
        );
        $this->firstTestObject = $this->getMock(
            '\Oro\Bundle\DataGridBundle\Datasource\ResultRecord',
            array(),
            array(),
            '',
            false
        );
        $this->secondTestObject = $this->getMock(
            '\Oro\Bundle\DataGridBundle\Datasource\ResultRecord',
            array(),
            array(),
            '',
            false
        );
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


    }

    public function setUp()
    {
        $this->target = new MergeMassActionHandler();

        $this->initMockObjects();

        $this->firstEntity = new Account();

        $this->firstEntity->setId(1);

        $this->secondEntity = new Account();

        $this->secondEntity->setId(2);

        $this->setUpMockObjects();
    }


    private function setIteratedResultMock()
    {
        $this->fakeResult->expects($this->at(0))
            ->method('rewind');

        $this->firstTestObject->expects($this->any())->method('getValue')->will($this->returnValue(1));
        $this->secondTestObject->expects($this->any())->method('getValue')->will($this->returnValue(2));
        // iteration 1
        $this->fakeResult->expects($this->at(1))
            ->method('valid')
            ->will($this->returnValue(true));
        $this->fakeResult->expects($this->at(2))
            ->method('current')
            ->will($this->returnValue($this->firstTestObject));
        $this->fakeResult->expects($this->at(3))
            ->method('next');
        $this->fakeResult->expects($this->at(4))
            ->method('valid')
            ->will($this->returnValue(true));
        $this->fakeResult->expects($this->at(5))
            ->method('current')
            ->will($this->returnValue($this->secondTestObject));
    }

    /**
     * @return mixed
     */
    private function setUpMockObjects()
    {
        $this->dataProvider
            ->expects($this->any())
            ->method(
                'getEntityIdentifier'
            )
            ->withAnyParameters()
            ->will($this->returnValue('id'));
        $this->dataProvider
            ->expects($this->any())
            ->method(
                'getEntitiesByPk'
            )
            ->withAnyParameters()
            ->will($this->returnValue(array($this->firstEntity, $this->secondEntity)));

        $this->target->setMergeDataProvider($this->dataProvider);


        $options = & $this->fakeOptionsArray;
        $this->fakeOptions
            ->expects($this->any())
            ->method('toArray')
            ->withAnyParameters()
            ->will(
                $this->returnCallback(
                    function () use (&$options) {
                        return $options;
                    }
                )
            );
        $this->setCorrectOptions();

        $fakeMassAction = $this->getMock(
            'Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface',
            array(),
            array(),
            '',
            false
        );

        $fakeMassAction->expects($this->any())->method('getOptions')->will($this->returnValue($this->fakeOptions));

        $this->fakeArgs
            ->expects($this->any())
            ->method('getMassAction')
            ->withAnyParameters()
            ->will($this->returnValue($fakeMassAction));
        $this->fakeArgs
            ->expects($this->any())
            ->method('getResults')
            ->withAnyParameters()
            ->will($this->returnValue($this->fakeResult));
    }

    protected function setCorrectOptions()
    {
        $this->fakeOptionsArray = array(
            'entity_name'       => 'test_entity',
            'max_element_count' => 5
        );
    }

    public function testMethodDoesNotThrowAnExceptionIfAllDataIsCorrect()
    {
        $this->setIteratedResultMock();
        $result = $this->target->handle($this->fakeArgs);

        $this->assertArrayHasKey('entities', $result);
        $this->assertEquals(2, count($result['entities']));
        $this->assertArrayHasKey('options', $result);
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
     * @expectedExceptionMessage Max element count invalid
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfMaxElementCountIsEmpty()
    {
        $this->fakeOptionsArray = array('entity_name' => 'test_entity', 'max_element_count' => '');

        $this->target->handle($this->fakeArgs);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Count of selected items less then 2
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfCountOfItemsToMergeLessThan2()
    {
        $this->target->handle($this->fakeArgs);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Too many items selected
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfCountOfItemsToMergeMoreThanMaxAllowedInConfig()
    {
        $this->fakeOptionsArray['max_element_count'] = 2;
        $this->setIteratedResultMock();
        $this->fakeResult->expects($this->at(6))
            ->method('next');
        $this->fakeResult->expects($this->at(7))
            ->method('valid')
            ->will($this->returnValue(true));
        $this->fakeResult->expects($this->at(8))
            ->method('current')
            ->will($this->returnValue($this->secondTestObject));


        $this->target->handle($this->fakeArgs);
    }

    public function testHandleMustReturnRequestedEntitiesForMerge()
    {
        $this->setIteratedResultMock();

        $result = $this->target->handle($this->fakeArgs);

        $actual = $result['entities'];
        /**
         * @var Account $firstActual
         */
        $firstActual = $actual[0];
        /**
         * @var Account $firstActual
         */
        $secondActual = $actual[1];

        $this->assertEquals($firstActual->getId(), 1);
        $this->assertEquals($secondActual->getId(), 2);
    }
}
