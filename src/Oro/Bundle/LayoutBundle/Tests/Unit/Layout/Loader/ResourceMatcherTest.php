<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceMatcher;

class ResourceMatcherTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResourceMatcher */
    protected $matcher;

    protected function setUp()
    {
        $this->matcher = new ResourceMatcher();
    }

    protected function tearDown()
    {
        unset($this->matcher);
    }

    public function testAddVoterUsePriorityForSorting()
    {
        $voter1 = $this->getMock('\Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface');
        $voter2 = $this->getMock('\Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface');
        $voter3 = $this->getMock('\Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface');
        $this->matcher->addVoter($voter1, 100);
        $this->matcher->addVoter($voter2, -10);
        $this->matcher->addVoter($voter3);

        $ref    = new \ReflectionClass($this->matcher);
        $method = $ref->getMethod('getVoters');
        $method->setAccessible(true);

        $this->assertSame([$voter1, $voter3, $voter2], $method->invoke($this->matcher));
    }

    public function testSetContext()
    {
        $context = $this->getMock('\Oro\Component\Layout\ContextInterface');

        $voter1 = $this->getMock('\Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface');
        $voter2 = $this->getMock('\Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\StubContextAwareVoter');
        $voter2->expects($this->once())->method('setContext')->with($this->identicalTo($context));

        $this->matcher->addVoter($voter1);
        $this->matcher->addVoter($voter2);
        $this->matcher->setContext($context);
    }

    public function testMatch()
    {
        $voter1 = $this->getMock('\Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface');
        $voter2 = $this->getMock('\Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface');
        $voter3 = $this->getMock('\Oro\Bundle\LayoutBundle\Layout\Loader\VoterInterface');
        $this->matcher->addVoter($voter1, 100);
        $this->matcher->addVoter($voter2, 0);
        $this->matcher->addVoter($voter3, -100);

        $result = true;

        $voter1->expects($this->once())->method('vote')->willReturn(null);
        $voter2->expects($this->once())->method('vote')->willReturn($result);
        $voter3->expects($this->never())->method('vote');

        $this->assertSame($result, $this->matcher->match([], ''));
    }
}
