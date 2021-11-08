<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Grid;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Grid\ConfigurationProvider;
use Oro\Bundle\SegmentBundle\Grid\SegmentDatagridConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class ConfigurationProviderTest extends SegmentDefinitionTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigurationProvider */
    private $provider;

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
            $this->getEntityNameResolver()
        );

        $builder->setConfigManager($this->configManager);

        $this->provider = new ConfigurationProvider($builder, $this->doctrine);
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->provider->isApplicable(Segment::GRID_PREFIX . '2'));
        $this->assertFalse($this->provider->isApplicable(Report::GRID_PREFIX . '2'));
    }

    public function testGetConfiguration()
    {
        $metadata = new EntityMetadata(User::class);
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($metadata);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($this->getSegment());

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repository);

        $this->provider->getConfiguration(Segment::GRID_PREFIX . '2');
    }

    /**
     * @dataProvider definitionProvider
     */
    public function testIsConfigurationValid(mixed $definition, bool $expectedResult)
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($this->getSegment($definition));

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repository);
        $result = $this->provider->isConfigurationValid(Segment::GRID_PREFIX . '2');
        $this->assertEquals($expectedResult, $result);
    }

    public function definitionProvider(): array
    {
        return [
            'valid'     => [$this->getDefaultDefinition(), true],
            'not valid' => [['empty array'], false]
        ];
    }

    public function testDoNotProcessInvalidSegmentGridName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The segment ID not found in the "oro_segment_grid_" grid name.');

        $this->provider->getConfiguration(Segment::GRID_PREFIX);
    }
}
