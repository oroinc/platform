<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\MandatoryFieldProviderInterface;
use Oro\Bundle\ApiBundle\Util\MandatoryFieldProviderRegistry;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MandatoryFieldProviderRegistryTest extends TestCase
{
    private MandatoryFieldProviderInterface&MockObject $provider1;
    private MandatoryFieldProviderInterface&MockObject $provider2;
    private MandatoryFieldProviderRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(MandatoryFieldProviderInterface::class);
        $this->provider2 = $this->createMock(MandatoryFieldProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('provider1', $this->provider1)
            ->add('provider2', $this->provider2)
            ->getContainer($this);

        $this->registry = new MandatoryFieldProviderRegistry(
            [
                ['provider1', 'json_api'],
                ['provider2', null]
            ],
            $container,
            new RequestExpressionMatcher()
        );
    }

    public function testFieldsShouldBeMergedAndMadeUnique(): void
    {
        $entityClass = 'Test\Class';

        $this->provider1->expects(self::once())
            ->method('getMandatoryFields')
            ->with($entityClass)
            ->willReturn(['field1', 'field2']);
        $this->provider2->expects(self::once())
            ->method('getMandatoryFields')
            ->with($entityClass)
            ->willReturn(['field2', 'field3']);

        self::assertEquals(
            ['field1', 'field2', 'field3'],
            $this->registry->getMandatoryFields($entityClass, new RequestType(['rest', 'json_api']))
        );
    }

    public function testFieldsShouldSkipNotSuitableProviders(): void
    {
        $entityClass = 'Test\Class';

        $this->provider1->expects(self::never())
            ->method('getMandatoryFields');
        $this->provider2->expects(self::once())
            ->method('getMandatoryFields')
            ->with($entityClass)
            ->willReturn(['field2', 'field3']);

        self::assertEquals(
            ['field2', 'field3'],
            $this->registry->getMandatoryFields($entityClass, new RequestType(['rest']))
        );
    }

    public function testFieldsShouldReturnEmptyArrayIfNoMandatoryFields(): void
    {
        $entityClass = 'Test\Class';

        $this->provider1->expects(self::once())
            ->method('getMandatoryFields')
            ->with($entityClass)
            ->willReturn([]);
        $this->provider2->expects(self::once())
            ->method('getMandatoryFields')
            ->with($entityClass)
            ->willReturn([]);

        self::assertEquals(
            [],
            $this->registry->getMandatoryFields($entityClass, new RequestType(['json_api']))
        );
    }
}
