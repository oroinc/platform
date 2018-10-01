<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository;
use Oro\Bundle\NotificationBundle\Event\Handler\TemplateEmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\NotificationProcessRecipientsEvent;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TemplateEmailNotificationAdapterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TemplateEmailNotificationAdapter */
    private $adapter;

    /** @var EmailHolderStub */
    private $entity;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailNotification */
    private $emailNotification;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $em;

    protected function setUp()
    {
        $this->entity = new EmailHolderStub();
        $this->emailNotification = $this->createMock(EmailNotification::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->adapter = new TemplateEmailNotificationAdapter(
            $this->entity,
            $this->emailNotification,
            $this->em,
            $this->getPropertyAccessor(),
            $this->eventDispatcher
        );
    }

    protected function tearDown()
    {
        unset($this->adapter, $this->entity, $this->emailNotification, $this->em);
    }

    public function testGetTemplateCriteria()
    {
        $emailTemplate = (new EmailTemplate('template_name'))->setEntityName('Entity/User');

        $this->emailNotification->expects($this->any())
            ->method('getTemplate')
            ->willReturn($emailTemplate);

        $this->assertEquals(
            new EmailTemplateCriteria('template_name', 'Entity/User'),
            $this->adapter->getTemplateCriteria()
        );
    }

    public function testGetRecipients()
    {
        $recipients = [new EmailAddressWithContext('email1@mail.com')];
        $this->mockRecipients(new RecipientList(), $recipients);

        $event = new NotificationProcessRecipientsEvent($this->entity, $recipients);
        $transformedRecipients = [new EmailHolderStub('email1@mail.com')];

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(NotificationProcessRecipientsEvent::NAME, $event)
            ->willReturnCallback(function (
                $eventName,
                NotificationProcessRecipientsEvent $event
            ) use ($transformedRecipients) {
                $event->setRecipients($transformedRecipients);
            });

        $this->assertEquals($transformedRecipients, $this->adapter->getRecipients());
    }

    public function testGetRecipientsFromAdditionalAssociations()
    {
        $subHolder1 = new EmailHolderStub('test1@example.com');
        $subHolder2 = new EmailHolderStub('test2@example.com');
        $subHolder3 = new EmailHolderStub('test3@example.com');
        $subHolder4 = new EmailHolderStub('test4@example.com');
        $subHolder3->setHolder($subHolder4);

        $this->entity->setHolder($subHolder1);
        $this->entity->setHolders([
            $subHolder2,
            $subHolder3,
        ]);

        $recipientList = new RecipientList();
        $recipientList->setAdditionalEmailAssociations([
            'holder',
            'holders',
            'holders.holder',
        ]);

        $this->mockRecipients($recipientList, []);
        $expectedRecipients = [
            $subHolder1,
            $subHolder2,
            $subHolder3,
            $subHolder4
        ];

        $this->assertEquals($expectedRecipients, $this->adapter->getRecipients());
    }

    public function testGetRecipientsFromAdditionalAssociationsAndUsers()
    {
        $subHolder1 = new EmailHolderStub('test1@example.com');
        $subHolder2 = new EmailHolderStub('test2@example.com');

        $this->entity->setHolders([
            $subHolder1,
            $subHolder2,
        ]);

        $recipientList = new RecipientList();
        $recipientList->setAdditionalEmailAssociations([
            'holders'
        ]);

        $this->mockRecipients($recipientList, [
            new EmailAddressWithContext('test2@example.com'),
            new EmailAddressWithContext('test3@example.com')
        ]);

        $expectedRecipients = [
            new EmailAddressWithContext('test2@example.com'),
            new EmailAddressWithContext('test3@example.com'),
            $subHolder1,
            $subHolder2
        ];

        $this->assertEquals($expectedRecipients, $this->adapter->getRecipients());
    }

    /**
     * @param mixed $email
     * @param array $expected
     *
     * @dataProvider emailValuesDataProvider
     */
    public function testGetRecipientsFromEntityEmails($email, $expected)
    {
        $recipientList = new RecipientList();
        $recipientList->setEntityEmails(['getEmail']);
        $this->entity->setEmail($email);
        $this->mockRecipients($recipientList, []);

        $actualResult = array_values($this->adapter->getRecipients());

        $this->assertEquals($expected, $actualResult);
    }

    /**
     * @return array
     */
    public function emailValuesDataProvider()
    {
        $testEmail = 'test1@example.com';
        $emailHolderStub = new EmailHolderStub($testEmail);

        return [
            'email as string' => [
                'actual'   => $testEmail,
                'expected' => [new EmailAddressWithContext($testEmail)]
            ],
            'email as array of strings' => [
                'actual'   => ['test2@example.com', 'test3@example.com'],
                'expected' => [
                    new EmailAddressWithContext('test2@example.com'),
                    new EmailAddressWithContext('test3@example.com')
                ]
            ],
            'email as object with EmailHolderInterface' => [
                'actual'   => $emailHolderStub,
                'expected' => [$emailHolderStub]
            ],
            'email as multidimensional array' => [
                'actual'   => [
                    new \stdClass(),
                    [
                        $testEmail,
                        [
                            [
                                new \stdClass(),
                                'test2@demo.com',
                                $emailHolderStub
                            ],
                            [
                                'test3@demo.com',
                                new \stdClass()
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    $emailHolderStub,
                    new EmailAddressWithContext('test2@demo.com'),
                    new EmailAddressWithContext('test3@demo.com')
                ]
            ]
        ];
    }

    /**
     * @param RecipientList $recipientList
     * @param array|EmailHolderInterface[] $recipients
     */
    private function mockRecipients(RecipientList $recipientList, $recipients)
    {
        $repository = $this->createMock(RecipientListRepository::class);
        $repository->expects($this->once())
            ->method('getRecipients')
            ->with($recipientList)
            ->willReturn($recipients);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(RecipientList::class)
            ->willReturn($repository);

        $this->emailNotification->expects($this->once())
            ->method('getRecipientList')
            ->willReturn($recipientList);
    }
}
