<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourceChecker;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class ResourceCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ResourceCheckerConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ResourceChecker */
    private $resourceChecker;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->configProvider = $this->createMock(ResourceCheckerConfigProvider::class);

        $this->resourceChecker = new ResourceChecker($this->featureChecker, $this->configProvider, 'api_resources');
    }

    public function testIsResourceDisabledForAllActions(): void
    {
        $entityClass = 'Test\Entity';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($entityClass, 'api_resources', self::isNull())
            ->willReturn(false);
        $this->configProvider->expects(self::never())
            ->method('getApiResourceFeatures');
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertFalse(
            $this->resourceChecker->isResourceEnabled(
                $entityClass,
                'get',
                '1.2.3',
                new RequestType([RequestType::REST])
            )
        );
    }

    public function testIsResourceEnabledForAllActions(): void
    {
        $entityClass = 'Test\Entity';
        $action = 'get';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($entityClass, 'api_resources', self::isNull())
            ->willReturn(true);
        $this->configProvider->expects(self::once())
            ->method('getApiResourceFeatures')
            ->with($entityClass, $action)
            ->willReturn([]);
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertTrue(
            $this->resourceChecker->isResourceEnabled(
                $entityClass,
                $action,
                '1.2.3',
                new RequestType([RequestType::REST])
            )
        );
    }

    public function testIsResourceEnabledForSpecificActions(): void
    {
        $entityClass = 'Test\Entity';
        $action = 'get';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($entityClass, 'api_resources', self::isNull())
            ->willReturn(true);
        $this->configProvider->expects(self::once())
            ->method('getApiResourceFeatures')
            ->with($entityClass, $action)
            ->willReturn(['feature1', 'feature2']);
        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->withConsecutive(['feature1'], ['feature2'])
            ->willReturn(true);

        self::assertTrue(
            $this->resourceChecker->isResourceEnabled(
                $entityClass,
                $action,
                '1.2.3',
                new RequestType([RequestType::REST])
            )
        );
    }

    public function testIsResourceDisabledForSpecificActions(): void
    {
        $entityClass = 'Test\Entity';
        $action = 'get';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($entityClass, 'api_resources', self::isNull())
            ->willReturn(true);
        $this->configProvider->expects(self::once())
            ->method('getApiResourceFeatures')
            ->with($entityClass, $action)
            ->willReturn(['feature1', 'feature2', 'feature3']);
        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->withConsecutive(['feature1'], ['feature2'])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertFalse(
            $this->resourceChecker->isResourceEnabled(
                $entityClass,
                $action,
                '1.2.3',
                new RequestType([RequestType::REST])
            )
        );
    }
}
