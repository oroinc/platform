<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Oro\Bundle\SecurityBundle\EventListener\ApiEventListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SoapBundle\Event\FindAfter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ApiEventListener */
    protected $listener;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $requestAuthorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var RequestStack */
    protected $requestStack;

    public function setUp()
    {
        $this->requestAuthorizationChecker = $this->createMock(RequestAuthorizationChecker::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->request = new Request();
        $this->requestStack = new RequestStack();

        $this->listener = new ApiEventListener(
            $this->requestAuthorizationChecker,
            $this->aclHelper,
            $this->requestStack
        );
    }

    /**
     * @dataProvider onFindAfterProvider
     */
    public function testOnFindAfter($isGranted, $throwException)
    {
        $this->requestStack->push($this->request);
        $object = new \stdClass();
        $this->requestAuthorizationChecker->expects($this->once())
            ->method('isRequestObjectIsGranted')
            ->with($this->request, $object)
            ->will($this->returnValue($isGranted));

        if ($throwException) {
            $this->expectException('Symfony\Component\Security\Core\Exception\AccessDeniedException');
        }
        $event = new FindAfter($object);
        $this->listener->onFindAfter($event);
    }

    public function onFindAfterProvider()
    {
        return [
            [-1, true],
            [0, false],
            [1, false],
        ];
    }

    public function testOnFindAfterNoRequest()
    {
        $this->requestAuthorizationChecker->expects($this->never())
            ->method($this->anything());

        $object = new \stdClass();
        $event = new FindAfter($object);
        $this->listener->onFindAfter($event);
    }
}
