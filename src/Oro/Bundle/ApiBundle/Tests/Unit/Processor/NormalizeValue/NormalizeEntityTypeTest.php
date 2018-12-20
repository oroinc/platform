<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeEntityType;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class NormalizeEntityTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasResolverRegistry */
    private $entityAliasResolverRegistry;

    /** @var NormalizeEntityType */
    private $processor;

    protected function setUp()
    {
        $this->entityAliasResolverRegistry = $this->createMock(EntityAliasResolverRegistry::class);

        $this->processor = new NormalizeEntityType($this->entityAliasResolverRegistry);
    }

    public function testProcess()
    {
        $context = new NormalizeValueContext();
        $context->setResult('Test\Class');

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('getEntityAliasResolver')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($entityAliasResolver);
        $entityAliasResolver->expects(self::once())
            ->method('getPluralAlias')
            ->with('Test\Class')
            ->willReturn('alias');

        $this->processor->process($context);

        self::assertEquals(NormalizeEntityType::REQUIREMENT, $context->getRequirement());
        self::assertEquals('alias', $context->getResult());
    }

    public function testProcessForArray()
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('Test\Class1,Test\Class2');

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('getEntityAliasResolver')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($entityAliasResolver);
        $entityAliasResolver->expects(self::exactly(2))
            ->method('getPluralAlias')
            ->willReturnMap(
                [
                    ['Test\Class1', 'alias1'],
                    ['Test\Class2', 'alias2']
                ]
            );

        $this->processor->process($context);

        self::assertEquals(
            $this->getArrayRequirement(NormalizeEntityType::REQUIREMENT),
            $context->getRequirement()
        );
        self::assertEquals(['alias1', 'alias2'], $context->getResult());
    }

    public function testProcessForAlreadyNormalizedAlias()
    {
        $context = new NormalizeValueContext();
        $context->setResult('alias');

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('getEntityAliasResolver')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($entityAliasResolver);
        $entityAliasResolver->expects(self::never())
            ->method('getPluralAlias');

        $this->processor->process($context);

        self::assertEquals('alias', $context->getResult());
    }

    public function testProcessWhenNoValueToNormalize()
    {
        $context = new NormalizeValueContext();

        $this->processor->process($context);

        self::assertFalse($context->hasResult());
    }

    public function testProcessForAlreadyResolvedRequirement()
    {
        $context = new NormalizeValueContext();
        $context->setRequirement('test');

        $this->processor->process($context);

        self::assertEquals('test', $context->getRequirement());
    }

    protected function getArrayRequirement($requirement)
    {
        return sprintf('%1$s(,%1$s)*', $requirement);
    }
}
