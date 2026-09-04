<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Feature;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Exception\DatagridDisabledException;
use Oro\Bundle\DataGridBundle\Extension\Feature\DatagridFeatureExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridFeatureExtensionTest extends TestCase
{
    private const string GRID_NAME = 'test-grid';

    private FeatureChecker&MockObject $featureChecker;
    private DatagridFeatureExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new DatagridFeatureExtension($this->featureChecker);
        $this->extension->setParameters(new ParameterBag());
    }

    private function getConfig(): DatagridConfiguration
    {
        return DatagridConfiguration::createNamed(self::GRID_NAME, []);
    }

    private function expectFeatureCheck(bool $enabled): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(self::GRID_NAME, 'datagrids')
            ->willReturn($enabled);
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->extension->isApplicable($this->getConfig()));
    }

    public function testGetPriorityIsLowerThanOtherExtensions(): void
    {
        self::assertSame(-300, $this->extension->getPriority());
    }

    public function testProcessConfigsWhenDatagridEnabled(): void
    {
        $this->expectFeatureCheck(true);

        $this->extension->processConfigs($this->getConfig());
    }

    public function testProcessConfigsWhenDatagridDisabled(): void
    {
        $this->expectFeatureCheck(false);

        $this->expectException(DatagridDisabledException::class);
        $this->expectExceptionMessage(
            'The "test-grid" datagrid is disabled because a feature this datagrid belongs to is disabled.'
        );

        $this->extension->processConfigs($this->getConfig());
    }
}
