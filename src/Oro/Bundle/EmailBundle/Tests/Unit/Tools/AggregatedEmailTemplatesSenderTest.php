<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class AggregatedEmailTemplatesSenderTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private LocalizedTemplateProvider|\PHPUnit\Framework\MockObject\MockObject $localizedTemplateProvider;

    private EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject $emailOriginHelper;

    private EmailModelSender|\PHPUnit\Framework\MockObject\MockObject $emailModelSender;

    private AggregatedEmailTemplatesSender $sender;

    private EmailTemplate|\PHPUnit\Framework\MockObject\MockObject $emailTemplate;

    private EntityOwnerAccessor|\PHPUnit\Framework\MockObject\MockObject $entityOwnerAccessor;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->localizedTemplateProvider = $this->createMock(LocalizedTemplateProvider::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
        $this->emailModelSender = $this->createMock(EmailModelSender::class);
        $this->emailTemplate = $this->createMock(EmailTemplate::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);

        $this->sender = new AggregatedEmailTemplatesSender(
            $this->doctrineHelper,
            $this->localizedTemplateProvider,
            $this->emailOriginHelper,
            $this->emailModelSender,
            $this->entityOwnerAccessor
        );

        $this->setUpLoggerMock($this->sender);
    }

    /**
     * Test with expected \Doctrine\ORM\EntityNotFoundException for the case, when template does not found
     */
    public function testExecuteWithoutTemplateEntity(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->localizedTemplateProvider->expects(self::once())
            ->method('getAggregated')
            ->willThrowException(new EntityNotFoundException());

        $this->emailModelSender->expects(self::never())
            ->method('send');

        $this->sender->send(
            new \stdClass(),
            [new Recipient('test@test.com')],
            From::emailAddress('test@test.com'),
            'test'
        );
    }

    public function testExecuteWithProcessException(): void
    {
        $rcpt = new Recipient('test@test.com');

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient($rcpt);

        $this->localizedTemplateProvider->expects(self::once())
            ->method('getAggregated')
            ->with([$rcpt], new EmailTemplateCriteria('test', \stdClass::class), ['entity' => new \stdClass()])
            ->willReturn([$dto]);

        $this->emailTemplate->expects(self::once())
            ->method('getType')
            ->willReturn('plain/text');

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with('test@test.com', null)
            ->willReturn($emailOrigin);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        $exception = new \RuntimeException('Sample exception');
        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(Email::class))
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send an email to test@test.com using "test" email template for "stdClass" '
                . 'entity: Sample exception',
                ['exception' => $exception]
            );

        $this->sender->send(
            new \stdClass(),
            [new Recipient('test@test.com')],
            From::emailAddress('test@test.com'),
            'test'
        );
    }

    /**
     * @dataProvider executeOptionsDataProvider
     */
    public function testExecute(array $options, string $recipient, array $expected): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        if (!$recipient instanceof EmailHolderInterface) {
            $recipient = new Recipient($recipient);
        }

        $organization = null;

        if ($organizationId = $options['organization_id']) {
            $organization = new Organization();
            $organization->setId($organizationId);

            $this->entityOwnerAccessor->expects($this->once())
                ->method('getOrganization')
                ->with($options['entity'])
                ->willReturn($organization);
        }

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient(is_object($recipient) ? $recipient : new Recipient($recipient));

        $this->localizedTemplateProvider->expects(self::once())
            ->method('getAggregated')
            ->with(
                [$recipient],
                new EmailTemplateCriteria('test', \stdClass::class),
                ['entity' => new \stdClass(), 'param' => 'value']
            )
            ->willReturn([$dto]);

        $this->emailTemplate->expects(self::once())
            ->method('getType')
            ->willReturn('plain/text');
        $this->emailTemplate->expects(self::once())
            ->method('getSubject')
            ->willReturn($expected['subject']);
        $this->emailTemplate->expects(self::once())
            ->method('getContent')
            ->willReturn($expected['body']);

        $emailEntity = $this->createMock(EmailEntity::class);

        $emailUserEntity = $this->createMock(EmailUser::class);
        $emailUserEntity->expects(self::any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($expected['from'], $organization)
            ->willReturn($emailOrigin);

        $sendEmail = (new Email())
            ->setFrom($expected['from'])
            ->setSubject($expected['subject'])
            ->setBody($expected['body'])
            ->setTo($expected['to'])
            ->setType('text');

        if ($organization) {
            $sendEmail->setOrganization($organization);
        }

        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with($sendEmail, $emailOrigin)
            ->willReturn($emailUserEntity);

        $this->sender->send(
            $options['entity'],
            [new Recipient($options['to'])],
            $options['from'],
            $options['template'],
            ['param' => 'value']
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeOptionsDataProvider(): array
    {
        return [
            'simple' => [
                'options' => [
                    'from' => From::emailAddress('test@test.com'),
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'organization_id' => null,
                ],
                'recipient' => 'test@test.com',
                'expected' => [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
            ],
            'simple with name' => [
                'options' => [
                    'from' => From::emailAddress('"Test" <test@test.com>'),
                    'to' => '"Test" <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'organization_id' => null,
                ],
                'recipient' => '"Test" <test@test.com>',
                'expected' => [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
            ],
            'simple with organization' => [
                'options' => [
                    'from' => From::emailAddress('test@test.com'),
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'organization_id' => 1,
                ],
                'recipient' => 'test@test.com',
                'expected' => [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
            ],
            'simple with name and organization' => [
                'options' => [
                    'from' => From::emailAddress('"Test" <test@test.com>'),
                    'to' => '"Test" <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'organization_id' => 2,
                ],
                'recipient' => '"Test" <test@test.com>',
                'expected' => [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
            ],
        ];
    }

    public function testExecuteWithMultipleRecipients(): void
    {
        $toEmail1 = 'to1@test.com';
        $toEmail2 = 'to2@test.com';

        $recipientEmail1 = 'recipient1@test.com';
        $recipient1 = new EmailHolderStub($recipientEmail1);

        $recipientEmail2 = 'recipient2@test.com';
        $recipient2 = new EmailHolderStub($recipientEmail2);

        $enTemplate = new EmailTemplate();
        $enTemplate->setSubject('subject_en');
        $enTemplate->setContent('body_en');
        $enTemplate->setType('txt');

        $deTemplate = new EmailTemplate();
        $deTemplate->setSubject('subject_de');
        $deTemplate->setContent('body_de');
        $deTemplate->setType('txt');

        $from = From::emailAddress('from@test.com');

        $rcpt1 = new Recipient($toEmail1);
        $rcpt2 = new Recipient($toEmail2);

        $dto1 = new LocalizedTemplateDTO($enTemplate);
        $dto1->addRecipient($rcpt1);
        $dto1->addRecipient($recipient1);

        $dto2 = new LocalizedTemplateDTO($deTemplate);
        $dto2->addRecipient($rcpt2);
        $dto2->addRecipient($recipient2);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        $this->localizedTemplateProvider->expects(self::once())
            ->method('getAggregated')
            ->with(
                [$rcpt1, $rcpt2, $recipient1, $recipient2],
                new EmailTemplateCriteria('test', \stdClass::class),
                ['entity' => new \stdClass()]
            )
            ->willReturn([$dto1, $dto2]);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects(self::exactly(2))
            ->method('getEmailOrigin')
            ->with($from->getAddress(), null)
            ->willReturn($emailOrigin);

        $this->emailModelSender->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    (new Email())
                        ->setFrom($from->toString())
                        ->setSubject($enTemplate->getSubject())
                        ->setBody($enTemplate->getContent())
                        ->setTo([$toEmail1, $recipientEmail1])
                        ->setType('text'),
                    $emailOrigin,
                ],
                [
                    (new Email())
                        ->setFrom($from->toString())
                        ->setSubject($deTemplate->getSubject())
                        ->setBody($deTemplate->getContent())
                        ->setTo([$toEmail2, $recipientEmail2])
                        ->setType('text'),
                    $emailOrigin,
                ]
            )
            ->willReturn(
                (new EmailUser())
                    ->setEmail(new EmailEntity())
            );

        $this->sender->send(new \stdClass(), [$rcpt1, $rcpt2, $recipient1, $recipient2], $from, 'test');
    }
}
