<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class ChainExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainExclusionProvider */
    private $chainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
    private $providers = [];

    protected function setUp(): void
    {
        $this->chainProvider = new ChainExclusionProvider();

        $highPriorityProvider = $this->createMock(ExclusionProviderInterface::class);
        $lowPriorityProvider = $this->createMock(ExclusionProviderInterface::class);

        $this->chainProvider->addProvider($highPriorityProvider);
        $this->chainProvider->addProvider($lowPriorityProvider);

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
    }

    public function testIsIgnoredEntityByLowPriorityProvider()
    {
        $this->providers[0]->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(true);
        $this->providers[1]->expects($this->never())
            ->method('isIgnoredEntity');

        $this->assertTrue($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredEntityByHighPriorityProvider()
    {
        $this->providers[0]->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(true);

        $this->assertTrue($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredEntityNone()
    {
        $this->providers[0]->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isIgnoredEntity')
            ->with('testClass')
            ->willReturn(false);

        $this->assertFalse($this->chainProvider->isIgnoredEntity('testClass'));
    }

    public function testIsIgnoredFieldByLowPriorityProvider()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(true);
        $this->providers[1]->expects($this->never())
            ->method('isIgnoredField');

        $this->assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredFieldByHighPriorityProvider()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(true);

        $this->assertTrue($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredFieldNone()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isIgnoredField')
            ->with($this->identicalTo($metadata), 'fieldName')
            ->willReturn(false);

        $this->assertFalse($this->chainProvider->isIgnoredField($metadata, 'fieldName'));
    }

    public function testIsIgnoredRelationByLowPriorityProvider()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(true);
        $this->providers[1]->expects($this->never())
            ->method('isIgnoredRelation');

        $this->assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }

    public function testIsIgnoredRelationByHighPriorityProvider()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(true);

        $this->assertTrue($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }

    public function testIsIgnoredRelationNone()
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->providers[0]->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($this->identicalTo($metadata), 'associationName')
            ->willReturn(false);

        $this->assertFalse($this->chainProvider->isIgnoredRelation($metadata, 'associationName'));
    }
}
