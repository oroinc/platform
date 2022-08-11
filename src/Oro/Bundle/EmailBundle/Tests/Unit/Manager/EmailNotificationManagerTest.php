<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Manager\EmailNotificationManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\EmailAddress;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailNotificationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var EmailNotificationManager */
    private $emailNotificationManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EmailRepository::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($this->repository);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects(self::any())
            ->method('purify')
            ->willReturnCallback(function (string $string) {
                return $string . ' (purified)';
            });

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('getEntityMetadata')
            ->willReturnCallback(function (string $class) {
                return new EntityMetadata($class);
            });

        $this->emailNotificationManager = new EmailNotificationManager(
            $doctrine,
            $htmlTagHelper,
            $this->urlGenerator,
            $configManager,
            $this->createMock(AclHelper::class)
        );
    }

    private function getEmailUser(
        int $id,
        string $subject,
        string $fromName,
        string $bodyContent,
        bool $hasFromEmailAddress,
        ?EmailOwnerInterface $fromEmailAddressOwner,
        bool $seen
    ): EmailUser {
        $emailBody = new EmailBody();
        $emailBody->setTextBody($bodyContent);

        $email = new Email();
        ReflectionUtil::setId($email, $id);
        $email->setSubject($subject);
        $email->setFromName($fromName);

        if ($hasFromEmailAddress) {
            $emailAddress = new EmailAddress();
            if (null !== $fromEmailAddressOwner) {
                $emailAddress->setOwner($fromEmailAddressOwner);
            }
            $email->setFromEmailAddress($emailAddress);
        }
        $email->setEmailBody($emailBody);

        $emailUser = new EmailUser();
        $emailUser->setEmail($email);
        $emailUser->setSeen($seen);

        return $emailUser;
    }

    public function testGetEmails(): void
    {
        $user = new User();

        $this->repository->expects(self::once())
            ->method('getNewEmails')
            ->willReturn([
                $this->getEmailUser(1, 'subject', 'fromName', 'bodyContent', true, $user, false),
                $this->getEmailUser(2, 'subject_1', 'fromName_1', 'bodyContent_1', true, $user, true)
            ]);

        $this->urlGenerator->expects(self::any())
            ->method('generate')
            ->willReturnArgument(0);

        $emails = $this->emailNotificationManager->getEmails($user, $this->createMock(Organization::class), 1, null);

        self::assertSame(
            [
                [
                    'replyRoute'    => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply_all',
                    'forwardRoute'  => 'oro_email_email_forward',
                    'id'            => 1,
                    'seen'          => false,
                    'subject'       => 'subject (purified)',
                    'bodyContent'   => 'bodyContent (purified)',
                    'fromName'      => 'fromName (purified)',
                    'linkFromName'  => 'oro_user_view'
                ],
                [
                    'replyRoute'    => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply_all',
                    'forwardRoute'  => 'oro_email_email_forward',
                    'id'            => 2,
                    'seen'          => true,
                    'subject'       => 'subject_1 (purified)',
                    'bodyContent'   => 'bodyContent_1 (purified)',
                    'fromName'      => 'fromName_1 (purified)',
                    'linkFromName'  => 'oro_user_view'
                ]
            ],
            $emails
        );
    }

    public function testGetEmailsWhenNoFromEmailAddress(): void
    {
        $user = new User();

        $this->repository->expects(self::once())
            ->method('getNewEmails')
            ->willReturn([
                $this->getEmailUser(1, 'subject', 'fromName', 'bodyContent', false, null, false)
            ]);

        $this->urlGenerator->expects(self::any())
            ->method('generate')
            ->willReturnArgument(0);

        $emails = $this->emailNotificationManager->getEmails($user, $this->createMock(Organization::class), 1, null);

        self::assertSame(
            [
                [
                    'replyRoute'    => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply_all',
                    'forwardRoute'  => 'oro_email_email_forward',
                    'id'            => 1,
                    'seen'          => false,
                    'subject'       => 'subject (purified)',
                    'bodyContent'   => 'bodyContent (purified)',
                    'fromName'      => 'fromName (purified)',
                    'linkFromName'  => null
                ]
            ],
            $emails
        );
    }

    public function testGetEmailsWhenNoFromEmailAddressOwner(): void
    {
        $user = new User();

        $this->repository->expects(self::once())
            ->method('getNewEmails')
            ->willReturn([
                $this->getEmailUser(1, 'subject', 'fromName', 'bodyContent', true, null, false)
            ]);

        $this->urlGenerator->expects(self::any())
            ->method('generate')
            ->willReturnArgument(0);

        $emails = $this->emailNotificationManager->getEmails($user, $this->createMock(Organization::class), 1, null);

        self::assertSame(
            [
                [
                    'replyRoute'    => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply_all',
                    'forwardRoute'  => 'oro_email_email_forward',
                    'id'            => 1,
                    'seen'          => false,
                    'subject'       => 'subject (purified)',
                    'bodyContent'   => 'bodyContent (purified)',
                    'fromName'      => 'fromName (purified)',
                    'linkFromName'  => null
                ]
            ],
            $emails
        );
    }

    public function testGetEmailsWhenViewRouteNotFound(): void
    {
        $user = new User();

        $this->repository->expects(self::once())
            ->method('getNewEmails')
            ->willReturn([
                $this->getEmailUser(1, 'subject', 'fromName', 'bodyContent', true, $user, false)
            ]);

        $this->urlGenerator->expects(self::any())
            ->method('generate')
            ->willReturnCallback(function (string $name) {
                if ('oro_user_view' === $name) {
                    throw new RouteNotFoundException('no route');
                }

                return $name;
            });

        $emails = $this->emailNotificationManager->getEmails($user, $this->createMock(Organization::class), 1, null);

        self::assertSame(
            [
                [
                    'replyRoute'    => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply_all',
                    'forwardRoute'  => 'oro_email_email_forward',
                    'id'            => 1,
                    'seen'          => false,
                    'subject'       => 'subject (purified)',
                    'bodyContent'   => 'bodyContent (purified)',
                    'fromName'      => 'fromName (purified)',
                    'linkFromName'  => null
                ]
            ],
            $emails
        );
    }

    public function testGetCountNewEmails(): void
    {
        $this->repository->expects(self::once())
            ->method('getCountNewEmails')
            ->willReturn(1);

        $count = $this->emailNotificationManager->getCountNewEmails(
            $this->createMock(User::class),
            $this->createMock(Organization::class)
        );

        self::assertEquals(1, $count);
    }
}
