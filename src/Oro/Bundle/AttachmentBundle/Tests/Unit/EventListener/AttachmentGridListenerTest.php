<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\EventListener\AttachmentGridListener;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestGridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new AttachmentGridListener(['entityId']);
    }

    public function testOnBuildBefore()
    {
        $gridConfig = new TestGridConfiguration();

        $parameters = new ParameterBag([AttachmentGridListener::GRID_PARAM_FIELD_NAME => 'testField']);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $event = new BuildBefore($datagrid, $gridConfig);
        $this->listener->onBuildBefore($event);

        $leftJoins = $gridConfig->offsetGetByPath('[source][query][join][left]');
        $this->assertEquals(
            [
                [
                    'join' => 'attachment.testField',
                    'alias' => 'entity'
                ]
            ],
            $leftJoins
        );
    }

    public function testOnBuildBeforeWhichTableName()
    {
        $gridConfig = new TestGridConfiguration();

        $parameters = new ParameterBag([
            AttachmentGridListener::GRID_PARAM_FIELD_NAME => 'testField',
            AttachmentGridListener::GRID_PARAM_TABLE_NAME => 'test_table'
        ]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $event = new BuildBefore($datagrid, $gridConfig);
        $this->listener->onBuildBefore($event);

        $fieldName = ExtendHelper::buildToManyRelationTargetFieldName('test_table', 'testField');

        $leftJoins = $gridConfig->offsetGetByPath('[source][query][join][left]');
        $this->assertEquals(
            [
                [
                    'join' => sprintf('attachment.%s', $fieldName),
                    'alias' => 'entity'
                ]
            ],
            $leftJoins
        );
    }

    public function testOnBuildAfter()
    {
        $entityId = 458;

        $parameters = new ParameterBag(['entityId' => $entityId]);
        $datasource = $this->createMock(OrmDatasource::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $qb = $this->createMock(QueryBuilder::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('setParameters')
            ->with(['entityId' => $entityId]);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
