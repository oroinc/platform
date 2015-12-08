<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeEntityAlias;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeEntityAliasTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var NormalizeEntityAlias */
    protected $processor;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityAlias($this->entityAliasResolver);
    }

    public function testProcess()
    {
        $context = new NormalizeValueContext();
        $context->setResult('alias');

        $this->entityAliasResolver->expects($this->once())
            ->method('getClassByAlias')
            ->with('alias')
            ->willReturn('Test\Class');

        $this->processor->process($context);

        $this->assertEquals(NormalizeEntityAlias::REQUIREMENT, $context->getRequirement());
        $this->assertEquals('Test\Class', $context->getResult());
    }

    public function testProcessForArray()
    {
        $context = new NormalizeValueContext();
        $context->setArrayDelimiter(',');
        $context->setArrayAllowed(true);
        $context->setResult('alias1,alias2');

        $this->entityAliasResolver->expects($this->exactly(2))
            ->method('getClassByAlias')
            ->willReturnMap(
                [
                    ['alias1', 'Test\Class1'],
                    ['alias2', 'Test\Class2'],
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            $this->getArrayRequirement(NormalizeEntityAlias::REQUIREMENT),
            $context->getRequirement()
        );
        $this->assertEquals(['Test\Class1', 'Test\Class2'], $context->getResult());
    }

    public function testProcessForAlreadyNormalizedAlias()
    {
        $context = new NormalizeValueContext();
        $context->setResult('Test\Class');

        $this->entityAliasResolver->expects($this->never())
            ->method('getClassByAlias');

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

    /**
     * @param string $requirement
     *
     * @return string
     */
    protected function getArrayRequirement($requirement)
    {
        return sprintf('%1$s(,%1$s)*', $requirement);
    }
}
