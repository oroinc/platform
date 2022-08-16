<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ChainResourceChecker;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerInterface;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ChainResourceCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResourceCheckerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $resourceCheckerRegistry;

    /** @var ChainResourceChecker */
    private $chainResourceChecker;

    protected function setUp(): void
    {
        $this->resourceCheckerRegistry = $this->createMock(ResourceCheckerRegistry::class);

        $this->chainResourceChecker = new ChainResourceChecker($this->resourceCheckerRegistry);
    }

    /**
     * @dataProvider isResourceEnabledDataProvider
     */
    public function testIsResourceEnabled(bool $result): void
    {
        $entityClass = 'Test\Entity';
        $action = 'GET';
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);

        $resourceChecker = $this->createMock(ResourceCheckerInterface::class);
        $resourceChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($entityClass, $action, $version, self::identicalTo($requestType))
            ->willReturn($result);

        $this->resourceCheckerRegistry->expects(self::once())
            ->method('getResourceChecker')
            ->with(self::identicalTo($requestType))
            ->willReturn($resourceChecker);

        self::assertSame(
            $result,
            $this->chainResourceChecker->isResourceEnabled($entityClass, $action, $version, $requestType)
        );
    }

    public function isResourceEnabledDataProvider(): array
    {
        return [[false], [true]];
    }
}
