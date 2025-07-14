<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\EventListener\DashboardTypeDatagridListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardTypeDatagridListenerTest extends TestCase
{
    private EnumOptionRepository&MockObject $repository;
    private TranslatorInterface&MockObject $translator;
    private DashboardTypeDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(EnumOptionRepository::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $enumOptinoProvider = $this->createMock(EnumOptionsProvider::class);

        $this->listener = new DashboardTypeDatagridListener($doctrine, $this->translator, $enumOptinoProvider);
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
            ['dashboard_type' => [
                'label' => 'translated_label',
                'editable' => false,
                'inline_editing' => ['enable' => false],
                'frontend_type' => 'select',
                'data_name' => 'dashboard_type',
                'choices' => [],
                'translatable_options' => false
            ]
            ],
            $config->offsetGet('columns')
        );
        self::assertEquals(
            ['columns' => [
                'dashboard_type' => [
                    'type' => 'enum',
                    'data_name' => 'dashboard_type',
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
