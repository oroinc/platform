<?php

namespace Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Manager\EmailNotificationManager;

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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $htmlTagHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var EmailNotificationManager */
    protected $emailNotificationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    protected function setUp() {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getNewEmails','getCountNewEmails'])
            ->getMock();
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->htmlTagHelper = $this->getMockBuilder('Oro\Bundle\UIBundle\Tools\HtmlTagHelper')
            ->disableOriginalConstructor()->getMock();

        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()->getMock();

        $this->emailCacheManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Cache\EmailCacheManager')
            ->disableOriginalConstructor()->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

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
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')->disableOriginalConstructor()->getMock();
        $testEmails = $this->getEmails($user);
        $this->repository->expects($this->once())->method('getNewEmails')->willReturn($testEmails);
        $this->emailCacheManager->expects($this->exactly(2))->method('ensureEmailBodyCached')->willReturn(true);


        $maxEmailsDisplay = 1;

        $emails = $this->emailNotificationManager->getEmails($user, $organization, $maxEmailsDisplay);

        $this->assertEquals(
           [
               [
                   'route' => '',
                   'id' => 1,
                   'seen' => 0,
                   'subject' => 'subject',
                   'bodyContent' => '',
                   'fromName' => 'fromName',
                   'linkFromName' => '',
               ],
               [
                   'route' => '',
                   'id' => 2,
                   'seen' => 1,
                   'subject' => 'subject_1',
                   'bodyContent' => '',
                   'fromName' => 'fromName_1',
                   'linkFromName' => '',
               ]
           ],
           $emails
        );
    }

    public function testGetCountNewEmails() {
        $this->repository->expects($this->once())->method('getCountNewEmails')->willReturn(1);
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')->disableOriginalConstructor()->getMock();
        $count = $this->emailNotificationManager->getCountNewEmails($user, $organization);
        $this->assertEquals(1, $count);
    }

    protected function getEmails($user)
    {
        return [
            [
                'seen'=>0,
                0 => $this->getMockEmail([
                    'getId' => 1,
                    'getSubject' => 'subject',
                    'getFromName' => 'fromName',
                    'getBodyContent' => 'bodyContent',
                ], $user)
            ],
            [
                'seen'=>1,
                0 => $this->getMockEmail([
                    'getId' => 2,
                    'getSubject' => 'subject_1',
                    'getFromName' => 'fromName_1',
                    'getBodyContent' => 'bodyContent_1',
                ], $user)
            ]
        ];
    }

    protected function getMockEmail($values, $user)
    {
        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')->disableOriginalConstructor()
            ->getMock();
        $email->expects($this->exactly(2))->method('getId')->willReturn($values['getId']);
        $email->expects($this->once())->method('getSubject')->willReturn($values['getSubject']);
        $email->expects($this->once())->method('getFromName')->willReturn($values['getFromName']);

        $emailAddress = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailAddress')->disableOriginalConstructor()->getMock();
        $emailAddress->expects($this->any())->method('getOwner')->willReturn($user);
        $email->expects($this->any())->method('getFromEmailAddress')->willReturn($emailAddress);

        $emailBody = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailBody')->disableOriginalConstructor()
            ->getMock();
        $emailBody->expects($this->once())->method('getBodyContent')->willReturn('bodyContent');
        $email->expects($this->once())->method('getEmailBody')->willReturn($emailBody);

        return $email;
    }
}
