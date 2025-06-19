<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeEntityClass;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NormalizeEntityClassTest extends TestCase
{
    private EntityAliasResolverRegistry&MockObject $entityAliasResolverRegistry;
    private NormalizeEntityClass $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityAliasResolverRegistry = $this->createMock(EntityAliasResolverRegistry::class);

        $this->processor = new NormalizeEntityClass($this->entityAliasResolverRegistry);
    }

    private function getArrayRequirement(string $requirement): string
    {
        return sprintf('%1$s(,%1$s)*', $requirement);
    }

    public function testProcess(): void
    {
        $context = new NormalizeValueContext();
        $context->setResult('alias');

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('getEntityAliasResolver')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($entityAliasResolver);
        $entityAliasResolver->expects(self::once())
            ->method('getClassByPluralAlias')
            ->with('alias')
            ->willReturn('Test\Class');

        $this->processor->process($context);

        self::assertEquals('[a-zA-Z]\w+', $context->getRequirement());
        self::assertEquals('Test\Class', $context->getResult());
    }

    public function testProcessForArray(): void
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('alias1,alias2');

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('getEntityAliasResolver')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($entityAliasResolver);
        $entityAliasResolver->expects(self::exactly(2))
            ->method('getClassByPluralAlias')
            ->willReturnMap([
                ['alias1', 'Test\Class1'],
                ['alias2', 'Test\Class2']
            ]);

        $this->processor->process($context);

        self::assertEquals(
            $this->getArrayRequirement('[a-zA-Z]\w+'),
            $context->getRequirement()
        );
        self::assertEquals(['Test\Class1', 'Test\Class2'], $context->getResult());
    }

    public function testProcessForAlreadyNormalizedAlias(): void
    {
        $context = new NormalizeValueContext();
        $context->setResult('Test\Class');

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('getEntityAliasResolver')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($entityAliasResolver);
        $entityAliasResolver->expects(self::never())
            ->method('getClassByPluralAlias');

        $this->processor->process($context);

        self::assertEquals('Test\Class', $context->getResult());
    }

    public function testProcessWhenNoValueToNormalize(): void
    {
        $context = new NormalizeValueContext();

        $this->processor->process($context);

        self::assertFalse($context->hasResult());
    }

    public function testProcessForAlreadyResolvedRequirement(): void
    {
        $context = new NormalizeValueContext();
        $context->setRequirement('test');

        $this->processor->process($context);

        self::assertEquals('test', $context->getRequirement());
    }
}
