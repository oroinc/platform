<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Manager\EmailNotificationManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Class EmailNotificationManagerTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Unit\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailNotificationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var EmailNotificationManager */
    protected $emailNotificationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
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

        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->configManager->expects($this->any())->method('getEntityMetadata')->willReturn($metadata);

        $this->emailNotificationManager = new EmailNotificationManager(
            $this->entityManager,
            $this->htmlTagHelper,
            $this->router,
            $this->configManager,
            new EmailBodyHelper($this->htmlTagHelper)
        );
    }

    /**
     * @dataProvider getEmails
     */
    public function testGetEmails($user, $emails, $expectedResult)
    {
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository->expects($this->once())->method('getNewEmails')->willReturn($emails);
        $maxEmailsDisplay = 1;
        $emails = $this->emailNotificationManager->getEmails($user, $organization, $maxEmailsDisplay, null);

        $this->assertEquals($expectedResult, $emails);
    }

    /**
     * @return array
     */
    public function getEmails()
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();

        $emails = [
            $this->prepareEmailUser(
                [
                    'getId'          => 1,
                    'getSubject'     => 'subject',
                    'getFromName'    => 'fromName',
                    'getBodyContent' => 'bodyContent',
                ],
                $user,
                false
            ),
            $this->prepareEmailUser(
                [
                    'getId'          => 2,
                    'getSubject'     => 'subject_1',
                    'getBodyContent' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer',
                    'getFromName'    => 'fromName_1',
                ],
                $user,
                true
            )
        ];

        $expectedResult = [
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
                'bodyContent' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer',
                'fromName' => 'fromName_1',
                'linkFromName' => 'oro_email_email_reply',
            ]
        ];

        return [[$user, $emails, $expectedResult]];
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

    /**
     * @param array $values
     * @param EmailOwnerInterface $user
     * @param bool $seen
     *
     * @return EmailUser
     */
    protected function prepareEmailUser($values, $user, $seen)
    {
        $emailBody = new EmailBody();
        $emailBody->setTextBody($values['getBodyContent']);

        $email = new Email();
        $email->setId($values['getId']);
        $email->setSubject($values['getSubject']);
        $email->setFromName($values['getFromName']);

        $emailAddress = new EmailAddress();
        $emailAddress->setOwner($user);

        $email->setFromEmailAddress($emailAddress);
        $email->setEmailBody($emailBody);

        $emailUser = new EmailUser();
        $emailUser->setEmail($email);
        $emailUser->setSeen($seen);

        return $emailUser;
    }
}
