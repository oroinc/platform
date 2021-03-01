<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Matcher\Voter;

use Oro\Bundle\NavigationBundle\Menu\Matcher\Voter;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestVoterTest extends \PHPUnit\Framework\TestCase
{
    public function testUriVoterConstruct()
    {
        $uri = 'test.uri';

        $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())
            ->method('getRequestUri')
            ->will($this->returnValue($uri));

        $itemMock = $this->createMock('Knp\Menu\ItemInterface');
        $itemMock->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->returnValue($uri));

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $voter = new Voter\RequestVoter($requestStack);

        $this->assertTrue($voter->matchItem($itemMock));
    }
}
