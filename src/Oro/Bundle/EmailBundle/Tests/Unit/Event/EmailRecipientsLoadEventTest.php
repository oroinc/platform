<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\UserBundle\Entity\Email;

class EmailRecipientsLoadEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEventCanBeConstructed()
    {
        $relatedEntity = new Email();
        $event = new EmailRecipientsLoadEvent($relatedEntity, 'query', 10);

        $this->assertEquals([], $event->getEmails());
        $this->assertEquals(10, $event->getLimit());
        $this->assertEquals('query', $event->getQuery());
        $this->assertEquals($relatedEntity, $event->getRelatedEntity());
        $this->assertEquals(10, $event->getRemainingLimit());
        $this->assertEquals([], $event->getResults());
    }

    public function testSettingResults()
    {
        $relatedEntity = new Email();
        $event = new EmailRecipientsLoadEvent($relatedEntity, 'query', 10);

        $results = [
            [
                'id'   => 'mail@example.com',
                'text' => 'Mail <mail@example.com>',
            ],
            [
                'id'       => 'Section',
                'children' => [
                    [
                        'id'   => 'smail@example.com',
                        'text' => 'Smail <smail@exampple.com>',
                    ],
                ],
            ]
        ];

        $event->setResults($results);
        $this->assertEquals($event->getResults(), $results);
        $this->assertEquals(8, $event->getRemainingLimit());
        $this->assertEquals(
            ['mail@example.com', 'smail@example.com'],
            $event->getEmails()
        );
    }
}
