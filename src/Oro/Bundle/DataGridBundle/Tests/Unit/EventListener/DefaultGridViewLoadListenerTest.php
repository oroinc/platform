<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\EventListener\DefaultGridViewLoadListener;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultGridViewLoadListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SAMPLE_ALL_LABEL = 'Sample All Label';

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var GridViewsLoadEvent */
    private $event;

    /** @var DefaultGridViewLoadListener */
    private $listener;

    protected function setUp(): void
    {
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->mappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->with('SampleClass')
            ->willReturn(true);
        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->with('SampleClass')
            ->willReturn(
                new Config(
                    new EntityConfigId('entity', 'SampleClass'),
                    [
                        'plural_label' => 'sampleclass.entity_plural_label',
                        'grid_all_view_label' => 'sampleclass.entity_grid_all_view_label'
                    ]
                )
            );

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($this->configProvider);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->config = $this->createMock(DatagridConfiguration::class);

        $this->event = new GridViewsLoadEvent('sample-grid', $this->config, $this->getGridViews());

        $this->listener = new DefaultGridViewLoadListener(
            $this->entityClassResolver,
            $this->mappingProvider,
            $this->configManager,
            $this->translator
        );
    }

    private function getGridViews(): array
    {
        return [
            [
                'label' => 'Will be skipped',
            ],
            [
                'name' => GridViewsExtension::DEFAULT_VIEW_ID,
                'label' => GridViewsExtension::DEFAULT_VIEW_ID,
            ],
            [
                'name' => 'sample-grid-view',
                'label' => 'Sample grid view',
            ],
        ];
    }

    public function testOnViewsLoadWhenAllLabelInOptions(): void
    {
        $this->config->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[options][gridViews][allLabel]')
            ->willReturn($allLabel = 'sample_all_label');

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['sampleclass.entity_plural_label', [], null, null, $entityPluralLabel = 'SamplePluralLabel'],
                [$allLabel, ['%entity_plural_label%' => $entityPluralLabel], null, null, self::SAMPLE_ALL_LABEL]
            ]);

        $this->config->expects($this->once())
            ->method('isOrmDatasource')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('getOrmQuery')
            ->willReturn($query = $this->createMock(OrmQueryConfiguration::class));

        $query->expects($this->once())
            ->method('getRootEntity')
            ->with($this->entityClassResolver, true)
            ->willReturn('SampleClass');

        $this->listener->onViewsLoad($this->event);

        $this->assertEquals($this->getExpectedGridViews(), $this->event->getGridViews());
    }

    private function getExpectedGridViews(): array
    {
        return [
            [
                'label' => 'Will be skipped',
            ],
            [
                'name' => GridViewsExtension::DEFAULT_VIEW_ID,
                'label' => self::SAMPLE_ALL_LABEL,
            ],
            [
                'name' => 'sample-grid-view',
                'label' => 'Sample grid view',
            ],
        ];
    }

    public function testOnViewsLoadWhenOrmDataSource(): void
    {
        $this->config->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[options][gridViews][allLabel]')
            ->willReturn(null);

        $this->config->expects($this->once())
            ->method('isOrmDatasource')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('getOrmQuery')
            ->willReturn($query = $this->createMock(OrmQueryConfiguration::class));

        $query->expects($this->once())
            ->method('getRootEntity')
            ->with($this->entityClassResolver, true)
            ->willReturn('SampleClass');

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['sampleclass.entity_plural_label', [], null, null, $entityPluralLabel = 'SamplePluralLabel'],
                [
                    'sampleclass.entity_grid_all_view_label',
                    ['%entity_plural_label%' => $entityPluralLabel],
                    null,
                    null,
                    self::SAMPLE_ALL_LABEL,
                ],
            ]);

        $this->listener->onViewsLoad($this->event);

        $this->assertEquals($this->getExpectedGridViews(), $this->event->getGridViews());
    }

    public function testOnViewsLoadWhenOrmDataSourceAndNoRootEntity(): void
    {
        $this->config->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[options][gridViews][allLabel]')
            ->willReturn(null);

        $this->config->expects($this->once())
            ->method('isOrmDatasource')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('getOrmQuery')
            ->willReturn($query = $this->createMock(OrmQueryConfiguration::class));

        $query->expects($this->once())
            ->method('getRootEntity')
            ->with($this->entityClassResolver, true)
            ->willReturn(null);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->listener->onViewsLoad($this->event);

        $this->assertEquals($this->getGridViews(), $this->event->getGridViews());
    }

    public function testOnViewsLoadWhenSearchDataSource(): void
    {
        $this->config->expects($this->exactly(2))
            ->method('offsetGetByPath')
            ->willReturnMap([
                ['[options][gridViews][allLabel]', null, null],
                [DatagridConfiguration::FROM_PATH, null, [$alias = 'sample_alias']],
            ]);

        $this->config->expects($this->once())
            ->method('isOrmDatasource')
            ->willReturn(false);

        $this->config->expects($this->once())
            ->method('getDatasourceType')
            ->willReturn(SearchDatasource::TYPE);

        $this->mappingProvider->expects($this->once())
            ->method('getEntityClass')
            ->with($alias)
            ->willReturn('SampleClass');

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['sampleclass.entity_plural_label', [], null, null, $entityPluralLabel = 'SamplePluralLabel'],
                [
                    'sampleclass.entity_grid_all_view_label',
                    ['%entity_plural_label%' => $entityPluralLabel],
                    null,
                    null,
                    self::SAMPLE_ALL_LABEL,
                ],
            ]);

        $this->listener->onViewsLoad($this->event);

        $this->assertEquals($this->getExpectedGridViews(), $this->event->getGridViews());
    }

    public function testOnViewsLoadWhenSearchDataSourceAndEmptyFromPath(): void
    {
        $this->config->expects($this->exactly(2))
            ->method('offsetGetByPath')
            ->willReturnMap([
                ['[options][gridViews][allLabel]', null, null],
                [DatagridConfiguration::FROM_PATH, null, []],
            ]);

        $this->config->expects($this->once())
            ->method('isOrmDatasource')
            ->willReturn(false);

        $this->config->expects($this->once())
            ->method('getDatasourceType')
            ->willReturn(SearchDatasource::TYPE);

        $this->mappingProvider->expects($this->never())
            ->method('getEntityClass');

        $this->translator->expects($this->never())
            ->method('trans');

        $this->listener->onViewsLoad($this->event);

        $this->assertEquals($this->getGridViews(), $this->event->getGridViews());
    }

    public function testOnViewsLoadWhenUnsupportedDataSourceType(): void
    {
        $this->config->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[options][gridViews][allLabel]')
            ->willReturn(null);

        $this->config->expects($this->once())
            ->method('isOrmDatasource')
            ->willReturn(false);

        $this->config->expects($this->once())
            ->method('getDatasourceType')
            ->willReturn('sample_type');

        $this->mappingProvider->expects($this->never())
            ->method('getEntityClass');

        $this->translator->expects($this->never())
            ->method('trans');

        $this->listener->onViewsLoad($this->event);

        $this->assertEquals($this->getGridViews(), $this->event->getGridViews());
    }
}
