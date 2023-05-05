<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\EventListener\DashboardTypeDatagridListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardTypeDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EnumValueRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DashboardTypeDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EnumValueRepository::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new DashboardTypeDatagridListener($doctrine, $this->translator);
    }

    public function testWhenMoreThenOneWidgetTypeInDb(): void
    {
        $this->repository->expects(self::once())
            ->method('count')
            ->willReturn(2);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.dashboard.dashboard_type.label')
            ->willReturn('translated_label');

        $config = DatagridConfiguration::create([]);
        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);

        $this->listener->onBuildBefore($event);
        self::assertEquals(
            ['dashboardType' => [
                'label' => 'translated_label',
                'editable' => false,
                'inline_editing' => ['enable' => false]
            ]],
            $config->offsetGet('columns')
        );
        self::assertEquals(
            ['query' => [
                'select' => ['dt.name as dashboardType'],
                'join' => ['left' => [['join' => 'dashboard.dashboard_type', 'alias' => 'dt']]]
            ]],
            $config->offsetGet('source')
        );
        self::assertEquals(
            ['columns' => [
                'dashboardType' => [
                    'type' => 'enum',
                    'data_name' => 'dt.id',
                    'enum_code' => 'dashboard_type'
                ]
            ]],
            $config->offsetGet('filters')
        );
    }

    public function testWhenOnlyOneWidgetTypeInDb(): void
    {
        $this->repository->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $this->translator->expects(self::never())
            ->method('trans');

        $config = DatagridConfiguration::create([]);
        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);

        $this->listener->onBuildBefore($event);
        self::assertEquals([], $config->offsetGetOr('columns', []));
        self::assertEquals([], $config->offsetGetOr('source', []));
        self::assertEquals([], $config->offsetGetOr('filters', []));
    }
}
