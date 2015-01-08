<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Form\Handler\CommentApiHandler;

class CommentApiHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var Comment
     */
    protected $comment;

    /**
     * @var CommentApiHandler
     */
    protected $handler;

    public function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->comment = new Comment();

        $this->handler = new CommentApiHandler($this->form, $this->request, $this->om, $this->configManager);
    }

    /**
     * @dataProvider getTestData
     *
     * @param string $type
     * @param int $callsCount
     * @param bool $valid
     * @param bool $expects
     */
    public function testRequest($type, $callsCount, $valid, $expects)
    {
        $persistCallsCount = 0;
        if ($valid && $callsCount) {
            $persistCallsCount = 1;
        }
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($type));

        $this->form->expects($this->exactly($callsCount))
            ->method('submit');

        $this->form->expects($this->exactly($callsCount))
            ->method('isValid')
            ->will($this->returnValue($valid));

        $this->om->expects($this->exactly($persistCallsCount))
            ->method('persist')
            ->with($this->equalTo($this->comment));
        $this->om->expects($this->exactly($persistCallsCount))
            ->method('flush');

        $this->assertEquals($expects, $this->handler->process($this->comment));
    }

    public function getTestData()
    {
        return [
            'correct request type GET' => ['GET', 0, true, false],
            'incorrect request type GET' => ['GET', 0, false, false],
            'correct request type DELETE' => ['DELETE', 0, true, false],
            'incorrect request type DELETE' => ['DELETE', 0, false, false],
            'correct request type POST' => ['POST', 1, true, true],
            'incorrect request type POST' => ['POST', 1, false, false],
            'correct request type PUT' => ['PUT', 1, true, true],
            'incorrect request type PUT' => ['PUT', 1, false, false],
        ];
    }
}
