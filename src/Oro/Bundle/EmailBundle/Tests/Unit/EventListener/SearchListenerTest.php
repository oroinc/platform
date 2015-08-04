<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\SearchListener;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;

use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestOrganization;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;

class SearchListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SearchListener */
    protected $listener;

    public function setUp()
    {
        $this->listener = new SearchListener();
    }

    /**
     * @dataProvider getTestData
     */
    public function testPrepareEntityMapEvent($entity, $expected)
    {
        $data = [
            'integer' => [
                'organization' => null
            ]
        ];
        $event = new PrepareEntityMapEvent($entity, get_class($entity), $data, []);
        $this->listener->prepareEntityMapEvent($event);
        $this->assertEquals($expected, $event->getData());
    }

    public function getTestData()
    {
        $badEmail = new Email();
        $badEmailAddress = new EmailAddress();
        $badEmailOwner = new TestEmailOwner();
        $badEmailAddress->setOwner($badEmailOwner);
        $badRecipient = new EmailRecipient();
        $badRecipient->setEmailAddress($badEmailAddress);
        $badEmail->addRecipient($badRecipient);

        $email = new Email();
        $emailAddress = new EmailAddress();
        $organization = new TestOrganization(3);
        $emailOwner = new TestUser(null, null, null, $organization);
        $emailAddress->setOwner($emailOwner);
        $recipient = new EmailRecipient();
        $recipient->setEmailAddress($emailAddress);
        $email->addRecipient($recipient);

        return [
            'wrong class' => [
                new \stdClass(),
                [
                    'integer' => [
                        'organization' => null
                    ]
                ]
            ],
            'email without correct user organization' => [
                $badEmail,
                [
                    'integer' => [
                        'organization' => 0
                    ]
                ]
            ],
            'correct email' => [
                $email,
                [
                    'integer' => [
                        'organization' => [3]
                    ]
                ]
            ]
        ];
    }
}
