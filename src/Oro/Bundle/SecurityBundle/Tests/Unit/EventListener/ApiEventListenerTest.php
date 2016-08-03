<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\Criteria;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\EventListener\ApiEventListener;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Oro\Bundle\SoapBundle\Event\FindAfter;
use Oro\Bundle\SoapBundle\Event\GetListBefore;

class ApiEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApiEventListener
     */
    protected $listener;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request();
        $this->listener = new ApiEventListener($this->securityFacade, $this->aclHelper);
    }

    /**
     * @dataProvider onFindAfterProvider
     */
    public function testOnFindAfter($isGranted, $throwException)
    {
        $this->listener->setRequest($this->request);
        $object = new \stdClass();
        $this->securityFacade->expects($this->once())
            ->method('isRequestObjectIsGranted')
            ->with($this->request, $object)
            ->will($this->returnValue($isGranted));

        if ($throwException) {
            $this->setExpectedException('Symfony\Component\Security\Core\Exception\AccessDeniedException');
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
        $this->securityFacade->expects($this->never())
            ->method($this->anything());

        $object = new \stdClass();
        $event = new FindAfter($object);
        $this->listener->onFindAfter($event);
    }
}
