<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Component\MessageQueue\Transport\Null\NullSession;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Async\ExpireUserPasswordsProcessor;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Entity\User;

class ExpireUserPasswordsProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpireUserPasswordsProcessor */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository */
    protected $userRepo;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailNotificationManager */
    protected $notificationManager;

    protected function setUp()
    {
        $userManager = $this->getMockForClass(UserManager::class);
        $doctrine = $this->getMockForClass(RegistryInterface::class);
        $em = $this->getMockForClass(ObjectManager::class);
        $logger = $this->getMockForClass(LoggerInterface::class);
        $emailTemplateRepo = $this->getMockForClass(ObjectRepository::class);
        $emailTemplate = $this->getMockForClass(EmailTemplate::class);

        $emailTemplateRepo->expects(self::atLeastOnce())
            ->method('findOneBy')
            ->willReturn($emailTemplate);

        $this->userRepo = $this->getMockForClass(ObjectRepository::class);

        $em->expects(self::atLeastOnce())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [EmailTemplate::class, $emailTemplateRepo],
                    [User::class, $this->userRepo],
                ]
            );
        $em->expects(self::once())->method('flush');

        $doctrine->expects(self::atLeastOnce())
            ->method('getEntityManagerForClass')
            ->willReturn($em);

        $this->notificationManager = $this->getMockForClass(EmailNotificationManager::class);
        $this->processor = new ExpireUserPasswordsProcessor(
            $this->notificationManager, $userManager, $doctrine, $logger
        );
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param array $userIds
     */
    public function testProcess($userIds = ['1'])
    {
        $session = new NullSession();
        $message = $session->createMessage(json_encode($userIds));
        $this->userRepo->expects(self::once())
            ->method('findBy')
            ->willReturnCallback(
                function () use ($userIds) {
                    $users = [];
                    foreach ($userIds as $userId) {
                        $user = new User();
                        $user->setId($userId);
                        $users[] = $user;
                    }

                    return $users;
                }
            );
        $this->notificationManager->expects(self::exactly(count($userIds)))->method('process');

        $this->processor->process($message, $session);
    }

    public function processDataProvider()
    {
        return [
            'no users' => [
                [],
            ],
            'some users' => [
                ['1', '3', '5'],
            ],
        ];
    }

    /**
     * Get Mock for class with disabled constructor
     *
     * @param $class
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|$class
     */
    protected function getMockForClass($class)
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
