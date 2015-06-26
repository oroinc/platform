<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;

class EmailRecipientsProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $dispatcher;

    protected $emailRecipientsProvider;

    public function setUp()
    {
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->emailRecipientsProvider = new EmailRecipientsProvider($this->dispatcher);
    }

    public function testDispatchShouldNotBeCalledIfThereAreNoListeners()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(EmailRecipientsLoadEvent::NAME)
            ->will($this->returnValue(false));

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $recipients = $this->emailRecipientsProvider->getEmailRecipients();

        $this->assertEquals([], $recipients);
    }

    public function testEmailRecipientsFromEventShouldBeReturned()
    {
        $recipients = [
            [
                'id'   => 'mail@example.com',
                'text' => 'Mail <mail@example.com>',
            ],
            [
                'text'     => 'Section',
                'children' => [
                    [
                        'id'   => 'smail@example.com',
                        'text' => 'Smail <smail@example.com>',
                    ],
                ],
            ],
        ];

        $expectedRecipients = [
            [
                'id'   => 'Mail <mail@example.com>',
                'text' => 'Mail <mail@example.com>',
            ],
            [
                'text'     => 'Section',
                'children' => [
                    [
                        'id'   => 'Smail <smail@example.com>',
                        'text' => 'Smail <smail@example.com>',
                    ],
                ],
            ],
        ];

        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(EmailRecipientsLoadEvent::NAME)
            ->will($this->returnValue(true));

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->will($this->returnCallback(function ($eventName, EmailRecipientsLoadEvent $event) use ($recipients) {
                $event->setResults($recipients);
            }));

        $actualRecipients = $this->emailRecipientsProvider->getEmailRecipients();

        $this->assertEquals($expectedRecipients, $actualRecipients);
    }
}
