<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Matcher\Voter;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Menu\Matcher\Voter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestVoterTest extends \PHPUnit\Framework\TestCase
{
    public function testUriVoterConstruct()
    {
        $uri = 'test.uri';

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn($uri);

        $itemMock = $this->createMock(ItemInterface::class);
        $itemMock->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri);

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $voter = new Voter\RequestVoter($requestStack);

        $this->assertTrue($voter->matchItem($itemMock));
    }
}
