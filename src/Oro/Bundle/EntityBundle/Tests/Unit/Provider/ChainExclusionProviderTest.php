<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class ChainExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    private ChainExclusionProvider $chainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
    private array $providers = [];

    protected function setUp(): void
    {
        $highPriorityProvider = $this->createMock(ExclusionProviderInterface::class);
        $lowPriorityProvider = $this->createMock(ExclusionProviderInterface::class);

        $this->chainProvider = new ChainExclusionProvider(new \ArrayIterator([$highPriorityProvider]));
        $this->chainProvider->addProvider($lowPriorityProvider);

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
    }

    public function testIsIgnoredEntityByLowPriorityProvider(): void
    {
        $this->providers[0]->expects(self::once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(true);
        $this->providers[1]->expects(self::never())
            ->method('isIgnoredEntity');

        self::assertTrue($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredEntityByHighPriorityProvider(): void
    {
        $this->providers[0]->expects(self::once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);
        $this->providers[1]->expects(self::once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(true);

        self::assertTrue($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredEntityNone(): void
    {
        $this->providers[0]->expects(self::once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);
        $this->providers[1]->expects(self::once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);

        self::assertFalse($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredFieldByLowPriorityProvider(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects(self::once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(true);
        $this->providers[1]->expects(self::never())
            ->method('isIgnoredField');

        self::assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredFieldByHighPriorityProvider(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects(self::once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(false);
        $this->providers[1]->expects(self::once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(true);

        self::assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredFieldNone(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects(self::once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(false);
        $this->providers[1]->expects(self::once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(false);

        self::assertFalse($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredRelationByLowPriorityProvider(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(true);
        $this->providers[1]->expects(self::never())
            ->method('isIgnoredRelation');

        self::assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }

    public function testIsIgnoredRelationByHighPriorityProvider(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(false);
        $this->providers[1]->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(true);

        self::assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }

    public function testIsIgnoredRelationNone(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(false);
        $this->providers[1]->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(false);

        self::assertFalse($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }
}
