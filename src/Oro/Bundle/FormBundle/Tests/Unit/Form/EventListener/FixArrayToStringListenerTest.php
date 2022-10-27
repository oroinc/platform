<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FormBundle\Form\EventListener\FixArrayToStringListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class FixArrayToStringListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider preBindDataProvider
     */
    public function testPreBind(string $delimiter, string|array $data, string $expectedData)
    {
        $event = new FormEvent($this->createMock(FormInterface::class), $data);
        $listener = new FixArrayToStringListener($delimiter);
        $listener->preSubmit($event);
        $this->assertEquals($expectedData, $event->getData());
    }

    public function preBindDataProvider(): array
    {
        return [
            'skip' => [
                ',',
                '1,2,3,4',
                '1,2,3,4',
            ],
            'convert array to string' => [
                ',',
                [1, 2, 3, 4],
                '1,2,3,4',
            ]
        ];
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::PRE_SUBMIT => 'preSubmit'],
            FixArrayToStringListener::getSubscribedEvents()
        );
    }
}
