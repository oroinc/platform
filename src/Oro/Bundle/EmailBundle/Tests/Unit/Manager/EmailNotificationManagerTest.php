<?php

namespace Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Class EmailNotificationManagerTest
 *
 * @package Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailNotificationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailCacheManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var EmailNotificationManager */
    protected $emailNotificationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getNewEmails','getCountNewEmails'])
            ->getMock();
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);


        $htmlTagProvider = $this->getMockBuilder('Oro\Bundle\FormBundle\Provider\HtmlTagProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->htmlTagHelper = new HtmlTagHelper($htmlTagProvider, '');

        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()->getMock();
        $this->router->expects($this->any())->method('generate')->willReturn('oro_email_email_reply');


        $this->emailCacheManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Cache\EmailCacheManager')
            ->disableOriginalConstructor()->getMock();

        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->configManager->expects($this->any())->method('getEntityMetadata')->willReturn($metadata);

        $this->emailNotificationManager = new EmailNotificationManager(
            $this->entityManager,
            $this->htmlTagHelper,
            $this->router,
            $this->emailCacheManager,
            $this->configManager
        );
    }

    public function testGetEmails()
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();
        $testEmails = $this->getEmails($user);
        $this->repository->expects($this->once())->method('getNewEmails')->willReturn($testEmails);
        $this->emailCacheManager->expects($this->exactly(2))->method('ensureEmailBodyCached')->willReturn(true);
        $maxEmailsDisplay = 1;
        $emails = $this->emailNotificationManager->getEmails($user, $organization, $maxEmailsDisplay, null);

        $this->assertEquals(
            [
                [
                    'replyRoute' => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply',
                    'forwardRoute' => 'oro_email_email_reply',
                    'id' => 1,
                    'seen' => 0,
                    'subject' => 'subject',
                    'bodyContent' => 'bodyContent',
                    'fromName' => 'fromName',
                    'linkFromName' => 'oro_email_email_reply',
                ],
                [
                    'replyRoute' => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply',
                    'forwardRoute' => 'oro_email_email_reply',
                    'id' => 2,
                    'seen' => 1,
                    'subject' => 'subject_1',
                    'bodyContent' => 'bodyContent_1',
                    'fromName' => 'fromName_1',
                    'linkFromName' => 'oro_email_email_reply',
                ]
            ],
            $emails
        );
    }

    public function testGetCountNewEmails()
    {
        $this->repository->expects($this->once())->method('getCountNewEmails')->willReturn(1);
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();
        $count = $this->emailNotificationManager->getCountNewEmails($user, $organization, null);
        $this->assertEquals(1, $count);
    }

    protected function getEmails($user)
    {
        $email = [
            'seen' => 0,
            0 => $this->getMockEmail([
                'getId' => 1,
                'getSubject' => 'subject',
                'getFromName' => 'fromName',
                'getBodyContent' => 'bodyContent',
            ], $user)
        ];

        $email1 = [
            'seen'=>1,
            0 => $this->getMockEmail([
                'getId' => 2,
                'getSubject' => 'subject_1',
                'getBodyContent' => 'bodyContent_1',
                'getFromName' => 'fromName_1',

            ], $user)
        ];

        return [$email, $email1];
    }

    protected function getMockEmail($values, $user)
    {
        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')->disableOriginalConstructor()
            ->getMock();
        $email->expects($this->exactly(1))->method('getId')->willReturn($values['getId']);
        $email->expects($this->once())->method('getSubject')->willReturn($values['getSubject']);
        $email->expects($this->once())->method('getFromName')->willReturn($values['getFromName']);
        $emailAddress = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailAddress')
            ->disableOriginalConstructor()
            ->getMock();
        $emailAddress->expects($this->any())->method('getOwner')->willReturn($user);
        $email->expects($this->any())->method('getFromEmailAddress')->willReturn($emailAddress);

        $emailBody = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailBody')->disableOriginalConstructor()
            ->getMock();
        $emailBody->expects($this->once())->method('getBodyContent')->willReturn($values['getBodyContent']);
        $email->expects($this->once())->method('getEmailBody')->willReturn($emailBody);

        return $email;
    }
}
