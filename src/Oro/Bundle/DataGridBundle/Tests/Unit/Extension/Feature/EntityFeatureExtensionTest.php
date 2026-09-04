<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Feature;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Exception\DatagridDisabledException;
use Oro\Bundle\DataGridBundle\Extension\Feature\EntityFeatureExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityFeatureExtensionTest extends TestCase
{
    private const string GRID_NAME = 'test-grid';
    private const string ENTITY_CLASS = 'Test\Entity\Test';

    private FeatureChecker&MockObject $featureChecker;
    private EntityFeatureExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new EntityFeatureExtension($this->featureChecker);
        $this->extension->setParameters(new ParameterBag());
    }

    private function getConfig(array $config = []): DatagridConfiguration
    {
        return DatagridConfiguration::createNamed(self::GRID_NAME, $config);
    }

    public function testIsApplicableWhenEntityDeclared(): void
    {
        self::assertTrue(
            $this->extension->isApplicable($this->getConfig(['extended_entity_name' => self::ENTITY_CLASS]))
        );
    }

    public function testIsNotApplicableWhenEntityNotDeclared(): void
    {
        self::assertFalse($this->extension->isApplicable($this->getConfig()));
    }

    /**
     * The root entity of the datasource query must not be taken into account.
     */
    public function testIsNotApplicableWhenOnlyDatasourceRootEntityExists(): void
    {
        self::assertFalse($this->extension->isApplicable($this->getConfig([
            'source' => ['type' => 'orm', 'query' => ['from' => [['table' => self::ENTITY_CLASS, 'alias' => 'e']]]]
        ])));
    }

    public function testIsNotApplicableWhenEntityStateIsIgnored(): void
    {
        self::assertFalse($this->extension->isApplicable($this->getConfig([
            'extended_entity_name' => self::ENTITY_CLASS,
            'features' => ['ignore_entity_state' => true]
        ])));
    }

    public function testIsApplicableWhenEntityStateIsNotIgnored(): void
    {
        self::assertTrue($this->extension->isApplicable($this->getConfig([
            'extended_entity_name' => self::ENTITY_CLASS,
            'features' => ['ignore_entity_state' => false]
        ])));
    }

    public function testGetPriorityIsLowerThanOtherExtensions(): void
    {
        self::assertSame(-300, $this->extension->getPriority());
    }

    public function testProcessConfigsWhenEntityEnabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(self::ENTITY_CLASS, 'entities')
            ->willReturn(true);

        $this->extension->processConfigs($this->getConfig(['extended_entity_name' => self::ENTITY_CLASS]));
    }

    public function testProcessConfigsWhenEntityDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(self::ENTITY_CLASS, 'entities')
            ->willReturn(false);

        $this->expectException(DatagridDisabledException::class);
        $this->expectExceptionMessage(
            'The "test-grid" datagrid is disabled because a feature the "Test\Entity\Test" entity belongs to'
            . ' is disabled.'
        );

        $this->extension->processConfigs($this->getConfig(['extended_entity_name' => self::ENTITY_CLASS]));
    }
}
