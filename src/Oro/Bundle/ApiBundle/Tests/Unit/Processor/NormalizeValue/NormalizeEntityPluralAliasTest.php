<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeEntityPluralAlias;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeEntityPluralAliasTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var NormalizeEntityPluralAlias */
    protected $processor;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityPluralAlias($this->entityAliasResolver);
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

        $this->assertEquals('[a-zA-Z]\w+', $context->getRequirement());
        $this->assertEquals('Test\Class', $context->getResult());
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
}
