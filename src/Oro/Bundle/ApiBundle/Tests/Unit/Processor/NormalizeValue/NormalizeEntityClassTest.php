<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeEntityClass;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeEntityClassTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var NormalizeEntityClass */
    protected $processor;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityClass($this->entityAliasResolver);
    }

    public function testProcess()
    {
        $context = new NormalizeValueContext();
        $context->setResult('alias');

        $this->entityAliasResolver->expects($this->once())
            ->method('getClassByPluralAlias')
            ->with('alias')
            ->willReturn('Test\Class');

        $this->processor->process($context);

        $this->assertEquals(NormalizeEntityClass::REQUIREMENT, $context->getRequirement());
        $this->assertEquals('Test\Class', $context->getResult());
    }

    public function testProcessForArray()
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('alias1,alias2');

        $this->entityAliasResolver->expects($this->exactly(2))
            ->method('getClassByPluralAlias')
            ->willReturnMap(
                [
                    ['alias1', 'Test\Class1'],
                    ['alias2', 'Test\Class2'],
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            $this->getArrayRequirement(NormalizeEntityClass::REQUIREMENT),
            $context->getRequirement()
        );
        $this->assertEquals(['Test\Class1', 'Test\Class2'], $context->getResult());
    }

    public function testProcessForAlreadyNormalizedAlias()
    {
        $context = new NormalizeValueContext();
        $context->setResult('Test\Class');

        $this->entityAliasResolver->expects($this->never())
            ->method('getClassByPluralAlias');

        $this->processor->process($context);

        $this->assertEquals('Test\Class', $context->getResult());
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
