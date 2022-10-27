<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class ExtendedAssociationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AssociationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $associationManager;

    /** @var ResourcesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resourcesProvider;

    /** @var EntityOverrideProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOverrideProviderRegistry;

    /** @var ExtendedAssociationProvider */
    private $extendedAssociationProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);
        $this->entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);

        $this->extendedAssociationProvider = new ExtendedAssociationProvider(
            $this->associationManager,
            $this->resourcesProvider,
            $this->entityOverrideProviderRegistry
        );
    }

    private function associationKindDataProvider(): array
    {
        return [
            ['testKind'],
            [null]
        ];
    }

    /**
     * @dataProvider associationKindDataProvider
     */
    public function testGetExtendedAssociationTargets(?string $associationKind): void
    {
        $entityClass = 'Test\Entity';
        $associationType = 'testType';
        $version = '1.1';
        $requestType = new RequestType([RequestType::REST]);
        $entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $this->entityOverrideProviderRegistry->expects(self::once())
            ->method('getEntityOverrideProvider')
            ->with($requestType)
            ->willReturn($entityOverrideProvider);
        $entityOverrideProvider->expects(self::exactly(4))
            ->method('getSubstituteEntityClass')
            ->willReturnMap([
                ['Test\Target1', null],
                ['Test\Target2', null],
                ['Test\Target3', 'Test\TargetModel3'],
                ['Test\Target4', 'Test\TargetModel4']
            ]);
        $this->resourcesProvider->expects(self::exactly(4))
            ->method('isResourceAccessible')
            ->willReturnMap([
                ['Test\Target1', $version, $requestType, true],
                ['Test\Target2', $version, $requestType, false],
                ['Test\TargetModel3', $version, $requestType, true],
                ['Test\TargetModel4', $version, $requestType, false]
            ]);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, self::isType('callable'), $associationType, $associationKind)
            ->willReturnCallback(function ($associationOwnerClass, $filter) {
                $relations = [
                    'Test\Target1' => 'field1',
                    'Test\Target2' => 'field2',
                    'Test\Target3' => 'field3',
                    'Test\Target4' => 'field4'
                ];
                $configManager = $this->createMock(ConfigManager::class);
                $associationTargets = [];
                foreach ($relations as $class => $field) {
                    if ($filter($associationOwnerClass, $class, $configManager)) {
                        $associationTargets[$class] = $field;
                    }
                }

                return $associationTargets;
            });

        $result = $this->extendedAssociationProvider->getExtendedAssociationTargets(
            $entityClass,
            $associationType,
            $associationKind,
            $version,
            $requestType
        );
        self::assertSame(['Test\Target1' => 'field1', 'Test\Target3' => 'field3'], $result);
    }

    /**
     * @dataProvider associationKindDataProvider
     */
    public function testFilterExtendedAssociationTargets(?string $associationKind): void
    {
        $entityClass = 'Test\Entity';
        $associationType = 'testType';

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, self::isNull(), $associationType, $associationKind)
            ->willReturn([
                'Test\Target1' => 'field1',
                'Test\Target2' => 'field2',
                'Test\Target3' => 'field3',
                'Test\Target4' => 'field4'
            ]);

        $result = $this->extendedAssociationProvider->filterExtendedAssociationTargets(
            $entityClass,
            $associationType,
            $associationKind,
            ['field1', 'field3']
        );
        self::assertSame(['Test\Target1' => 'field1', 'Test\Target3' => 'field3'], $result);
    }

    public function testFilterExtendedAssociationTargetsWhenTargetFieldNamesAreEmpty(): void
    {
        $entityClass = 'Test\Entity';
        $associationType = 'testType';
        $associationKind = 'testKind';

        $this->associationManager->expects(self::never())
            ->method('getAssociationTargets');

        $result = $this->extendedAssociationProvider->filterExtendedAssociationTargets(
            $entityClass,
            $associationType,
            $associationKind,
            []
        );
        self::assertSame([], $result);
    }
}
