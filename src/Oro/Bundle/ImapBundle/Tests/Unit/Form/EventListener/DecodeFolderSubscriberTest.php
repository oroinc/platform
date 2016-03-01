<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ImapBundle\Form\EventListener\DecodeFolderSubscriber;

class DecodeFolderSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var  DecodeFolderSubscriber */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new DecodeFolderSubscriber();
    }

    public function testDecodeFolderNoData()
    {
        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $formEvent->expects($this->exactly(1))
            ->method('getData')
            ->willReturn(null);
        $formEvent->expects($this->exactly(0))
            ->method('setData');
        $this->listener->decodeFolders($formEvent);
    }

    public function testDecodeFolderEmptyData()
    {
        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $formEvent->expects($this->exactly(1))
            ->method('getData')
            ->willReturn([]);
        $formEvent->expects($this->exactly(0))
            ->method('setData');
        $this->listener->decodeFolders($formEvent);
    }

    public function testDecodeFolderNoKeyData()
    {
        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $formEvent->expects($this->exactly(1))
            ->method('getData')
            ->willReturn(['test' => json_encode([])]);
        $formEvent->expects($this->exactly(0))
            ->method('setData');
        $this->listener->decodeFolders($formEvent);
    }

    public function testDecodeFolder()
    {
        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $folders = ['f1' => 1];
        $formEvent->expects($this->exactly(1))
            ->method('getData')
            ->willReturn(['folders' => json_encode($folders)]);
        $formEvent->expects($this->exactly(1))
            ->method('setData')
            ->with(['folders' => $folders]);
        $this->listener->decodeFolders($formEvent);
    }
}
