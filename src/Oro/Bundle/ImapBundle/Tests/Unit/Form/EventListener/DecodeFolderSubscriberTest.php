<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ImapBundle\Form\EventListener\DecodeFolderSubscriber;
use Symfony\Component\Form\FormEvent;

class DecodeFolderSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var DecodeFolderSubscriber */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new DecodeFolderSubscriber();
    }

    public function testDecodeFolderNoData()
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $formEvent->expects($this->exactly(0))
            ->method('setData');
        $this->listener->decodeFolders($formEvent);
    }

    public function testDecodeFolderEmptyData()
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn([]);
        $formEvent->expects($this->exactly(0))
            ->method('setData');
        $this->listener->decodeFolders($formEvent);
    }

    public function testDecodeFolderNoKeyData()
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn(['test' => json_encode([], JSON_THROW_ON_ERROR)]);
        $formEvent->expects($this->exactly(0))
            ->method('setData');
        $this->listener->decodeFolders($formEvent);
    }

    public function testDecodeFolder()
    {
        $formEvent = $this->createMock(FormEvent::class);
        $folders = ['f1' => 1];
        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn(['folders' => json_encode($folders, JSON_THROW_ON_ERROR)]);
        $formEvent->expects($this->once())
            ->method('setData')
            ->with(['folders' => $folders]);
        $this->listener->decodeFolders($formEvent);
    }
}
