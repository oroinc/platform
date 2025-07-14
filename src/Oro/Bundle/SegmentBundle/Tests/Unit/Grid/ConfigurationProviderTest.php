<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Grid;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Grid\ConfigurationProvider;
use Oro\Bundle\SegmentBundle\Grid\SegmentDatagridConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigurationProviderTest extends SegmentDefinitionTestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ConfigManager&MockObject $configManager;
    private ConfigurationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->getDoctrine(
            [self::TEST_ENTITY => []],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );

        $this->configManager = $this->createMock(ConfigManager::class);

        $builder = new SegmentDatagridConfigurationBuilder(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->getVirtualRelationProvider(),
            new DoctrineHelper($this->doctrine),
            new DatagridGuesser([]),
            $this->getEntityNameResolver(),
            $this->createMock(EnumTypeHelper::class)
        );

        $builder->setConfigManager($this->configManager);

        $this->provider = new ConfigurationProvider($builder, $this->doctrine);
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->provider->isApplicable(Segment::GRID_PREFIX . '2'));
        self::assertFalse($this->provider->isApplicable(Report::GRID_PREFIX . '2'));
    }

    public function testValidConfiguration(): void
    {
        $id = 2;
        $gridName = Segment::GRID_PREFIX . $id;
        $segmentRepository = $this->createMock(EntityRepository::class);
        $segmentRepository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn($this->getSegment(identifier: $id));

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($segmentRepository);

        self::assertTrue($this->provider->isValidConfiguration($gridName));
    }

    public function testNotValidConfiguration(): void
    {
        self::assertFalse($this->provider->isValidConfiguration(Segment::GRID_PREFIX . '2'));
    }

    public function testGetConfiguration(): void
    {
        $metadata = new EntityMetadata(User::class);
        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->willReturn($metadata);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(2)
            ->willReturn($this->getSegment());

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repository);

        $this->provider->getConfiguration(Segment::GRID_PREFIX . '2');
    }

    /**
     * @dataProvider definitionProvider
     */
    public function testIsConfigurationValid(mixed $definition, bool $expectedResult): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(2)
            ->willReturn($this->getSegment($definition));

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repository);
        $result = $this->provider->isConfigurationValid(Segment::GRID_PREFIX . '2');
        self::assertEquals($expectedResult, $result);
    }

    public function definitionProvider(): array
    {
        return [
            'valid'     => [$this->getDefaultDefinition(), true],
            'not valid' => [['empty array'], false]
        ];
    }

    public function testDoNotProcessInvalidSegmentGridName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The segment ID not found in the "oro_segment_grid_" grid name.');

        $this->provider->getConfiguration(Segment::GRID_PREFIX);
    }
}
