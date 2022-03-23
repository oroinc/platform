<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
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
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailNotificationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EmailNotificationManager */
    private $emailNotificationManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EmailRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($this->repository);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($em);

        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);

        $htmlTagHelper = new HtmlTagHelper($htmlTagProvider, '');
        $htmlTagHelper->setTranslator($this->createMock(TranslatorInterface::class));

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->any())
            ->method('generate')
            ->willReturn('oro_email_email_reply');

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn(new EntityMetadata(\stdClass::class));

        $this->emailNotificationManager = new EmailNotificationManager(
            $doctrine,
            $htmlTagHelper,
            $urlGenerator,
            $configManager
        );
        $this->emailNotificationManager->setAclHelper($this->createMock(AclHelper::class));
    }

    /**
     * @dataProvider getEmails
     */
    public function testGetEmails(User $user, array $emails, array $expectedResult)
    {
        $organization = $this->createMock(Organization::class);
        $this->repository->expects($this->once())
            ->method('getNewEmailsWithAcl')
            ->willReturn($emails);
        $maxEmailsDisplay = 1;
        $emails = $this->emailNotificationManager->getEmails($user, $organization, $maxEmailsDisplay, null);

        $this->assertEquals($expectedResult, $emails);
    }

    public function getEmails(): array
    {
        $user = $this->createMock(User::class);

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
        $this->repository->expects($this->once())
            ->method('getCountNewEmailsWithAcl')
            ->willReturn(1);
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $count = $this->emailNotificationManager->getCountNewEmails($user, $organization, null);
        $this->assertEquals(1, $count);
    }

    private function prepareEmailUser(array $values, EmailOwnerInterface $user, bool $seen): EmailUser
    {
        $emailBody = new EmailBody();
        $emailBody->setTextBody($values['getBodyContent']);

        $email = new Email();
        ReflectionUtil::setId($email, $values['getId']);
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
