<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeEntityType;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeEntityTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var NormalizeEntityType */
    protected $processor;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityType($this->entityAliasResolver);
    }

    public function testProcess()
    {
        $context = new NormalizeValueContext();
        $context->setResult('Test\Class');

        $this->entityAliasResolver->expects($this->once())
            ->method('getPluralAlias')
            ->with('Test\Class')
            ->willReturn('alias');

        $this->processor->process($context);

        $this->assertEquals(NormalizeEntityType::REQUIREMENT, $context->getRequirement());
        $this->assertEquals('alias', $context->getResult());
    }

    public function testProcessForArray()
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('Test\Class1,Test\Class2');

        $this->entityAliasResolver->expects($this->exactly(2))
            ->method('getPluralAlias')
            ->willReturnMap(
                [
                    ['Test\Class1', 'alias1'],
                    ['Test\Class2', 'alias2'],
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            $this->getArrayRequirement(NormalizeEntityType::REQUIREMENT),
            $context->getRequirement()
        );
        $this->assertEquals(['alias1', 'alias2'], $context->getResult());
    }

    public function testProcessForAlreadyNormalizedAlias()
    {
        $context = new NormalizeValueContext();
        $context->setResult('alias');

        $this->entityAliasResolver->expects($this->never())
            ->method('getPluralAlias');

        $this->processor->process($context);

        $this->assertEquals('alias', $context->getResult());
    }

    public function testProcessWhenNoValueToNormalize()
    {
        $context = new NormalizeValueContext();

        $this->processor->process($context);

        $this->assertFalse($context->hasResult());
    }

    public function testProcessForAlreadyResolvedRequirement()
    {
        $context = new NormalizeValueContext();
        $context->setRequirement('test');

        $this->processor->process($context);

        $this->assertEquals('test', $context->getRequirement());
    }

    protected function getArrayRequirement($requirement)
    {
        return sprintf('%1$s(,%1$s)*', $requirement);
    }
}
