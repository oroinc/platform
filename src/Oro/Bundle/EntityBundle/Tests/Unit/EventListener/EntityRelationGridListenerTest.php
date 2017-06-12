<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\EventListener\EntityRelationGridListener;

class EntityRelationGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $cm;

    /** @var EntityRelationGridListener */
    protected $listener;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new EntityRelationGridListener($this->cm, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->cm);
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array      $parameters
     * @param array|bool $expectedBindParamsCall
     */
    public function testOnBuildAfter(array $parameters, $expectedBindParamsCall)
    {
        $grid       = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()->getMock();

        $grid->expects($this->once())->method('getDatasource')->willReturn($datasource);
        $grid->expects($this->once())->method('getParameters')->willReturn(new ParameterBag($parameters));

        if ($expectedBindParamsCall) {
            $datasource->expects($this->once())->method('bindParameters')->with($expectedBindParamsCall);
        } else {
            $datasource->expects($this->never())->method('bindParameters');
        }

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
    }

    /**
     * @return array
     */
    public function parametersProvider()
    {
        return [
            'identifier found, expected bind of "relation" param' => [
                '$parameters'             => ['id' => rand(1, 100)],
                '$expectedBindParamsCall' => ['relation' => 'id'],
            ],
            'empty parameters, bind should not be performed'      => [
                '$parameters'             => [],
                '$expectedBindParamsCall' => false,
            ]
        ];
    }
}
