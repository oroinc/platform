<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use Oro\Bundle\TrackingBundle\Form\Handler\TrackingWebsiteHandler;

class TrackingWebsiteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request();

        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $method
     * @param bool   $formValid
     * @param bool   $isFlushCalled
     *
     * @dataProvider processProvider
     */
    public function testProcess($method, $isFormValid, $isFlushCalled)
    {
        $entity = new TrackingWebsite();

        $this->request->setMethod($method);

        $this->form
            ->expects($this->any())
            ->method('submit')
            ->with($this->request);

        $this->form
            ->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isFormValid));

        if ($isFlushCalled) {
            $this->manager
                ->expects($this->once())
                ->method('persist')
                ->with($this->equalTo($entity))
                ->will($this->returnValue(true));

            $this->manager
                ->expects($this->once())
                ->method('flush');
        }

        $handler = new TrackingWebsiteHandler(
            $this->form,
            $this->request,
            $this->manager
        );

        $handler->process($entity);
    }

    /**
     * @return array
     */
    public function processProvider()
    {
        return [
            ['POST', false, false],
            ['POST', true, true],
            ['GET', false, false],
            ['GET', true, false],
        ];
    }
}
