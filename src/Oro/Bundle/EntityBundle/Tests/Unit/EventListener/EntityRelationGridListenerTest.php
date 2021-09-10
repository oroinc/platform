<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\EventListener\EntityRelationGridListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class EntityRelationGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $cm;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityRelationGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cm = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new EntityRelationGridListener($this->cm, $this->doctrineHelper);
    }

    /**
     * @dataProvider parametersProvider
     */
    public function testOnBuildAfter(array $parameters, array|bool $expectedBindParamsCall)
    {
        $grid = $this->createMock(DatagridInterface::class);
        $datasource = $this->createMock(OrmDatasource::class);

        $grid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $grid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag($parameters));

        if ($expectedBindParamsCall) {
            $datasource->expects($this->once())
                ->method('bindParameters')
                ->with($expectedBindParamsCall);
        } else {
            $datasource->expects($this->never())
                ->method('bindParameters');
        }

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
    }

    public function parametersProvider(): array
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
