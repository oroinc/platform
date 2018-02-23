<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Component\Testing\Unit\EntityTrait;

class EmailNotificationAdapterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var EmailNotificationAdapter */
    private $adapter;

    /** @var EmailHolderStub */
    private $entity;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailNotification */
    private $emailNotification;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    private $em;

    protected function setUp()
    {
        $this->entity = new EmailHolderStub();
        $this->emailNotification = $this->getMockBuilder(EmailNotification::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter = new EmailNotificationAdapter(
            $this->entity,
            $this->emailNotification,
            $this->em,
            $this->getPropertyAccessor()
        );
    }

    protected function tearDown()
    {
        unset($this->adapter, $this->entity, $this->emailNotification, $this->em);
    }

    public function testGetTemplate()
    {
        $template = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');

        $this->emailNotification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template));

        $this->assertEquals($template, $this->adapter->getTemplate());
    }

    public function testGetRecipientEmails()
    {
        $emails = ['email'];
        $this->mockRecipientEmails(new RecipientList(), $emails);

        $this->assertEquals($emails, $this->adapter->getRecipientEmails());
    }

    public function testGetRecipientEmailsFromAdditionalAssociations()
    {
        $expectedEmails = [
            'test1@example.com',
            'test2@example.com',
            'test3@example.com',
            'test4@example.com',
        ];

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

        $this->mockRecipientEmails($recipientList, []);

        $this->assertEquals($expectedEmails, $this->adapter->getRecipientEmails());
    }

    /**
     * @param mixed $email
     * @param array $expected
     *
     * @dataProvider getEmailValues
     */
    public function testGetRecipientEmailsFromEntityEmails($email, $expected)
    {
        $recipientList = new RecipientList();
        $recipientList->setEntityEmails(['getEmail']);
        $this->entity->setEmail($email);
        $this->mockRecipientEmails($recipientList, []);

        $actualResult = array_values($this->adapter->getRecipientEmails());

        $this->assertEquals($expected, $actualResult);
    }

    /**
     * @return array
     */
    public function getEmailValues()
    {
        $testEmail = 'test1@example.com';
        $emailHolderStub = new EmailHolderStub($testEmail);

        return [
            'email as string' => [
                'actual'   => $testEmail,
                'expected' => [$testEmail]
            ],
            'email as array of strings' => [
                'actual'   => ['test2@example.com', 'test3@example.com'],
                'expected' => ['test2@example.com', 'test3@example.com']
            ],
            'email as object with EmailHolderInterface' => [
                'actual'   => $emailHolderStub,
                'expected' => [$testEmail]
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
                'expected' => [$testEmail, 'test2@demo.com', 'test3@demo.com']
            ]
        ];
    }

    /**
     * @param RecipientList $recipientList
     * @param array $emails
     */
    private function mockRecipientEmails(RecipientList $recipientList, $emails)
    {
        $repository = $this->createMock(RecipientListRepository::class);
        $repository->expects($this->once())
            ->method('getRecipientEmails')
            ->with($this->identicalTo($recipientList), $this->identicalTo($this->entity))
            ->will($this->returnValue($emails));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('Oro\Bundle\NotificationBundle\Entity\RecipientList')
            ->will($this->returnValue($repository));
        $this->emailNotification->expects($this->once())
            ->method('getRecipientList')
            ->will($this->returnValue($recipientList));
    }
}
