<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Psr\Log\LoggerInterface;

class AggregatedEmailTemplatesSenderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LocalizedTemplateProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localizedTemplateProvider;

    /** @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOriginHelper;

    /** @var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $emailProcessor;

    /** @var AggregatedEmailTemplatesSender */
    private $sender;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EmailTemplate|\PHPUnit\Framework\MockObject\MockObject */
    private $emailTemplate;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->localizedTemplateProvider = $this->createMock(LocalizedTemplateProvider::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
        $this->emailProcessor = $this->createMock(Processor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->emailTemplate = $this->createMock(EmailTemplate::class);

        $this->sender = new AggregatedEmailTemplatesSender(
            $this->doctrineHelper,
            $this->localizedTemplateProvider,
            $this->emailOriginHelper,
            $this->emailProcessor
        );
        $this->sender->setLogger($this->logger);
    }

    /**
     * Test with expected \Doctrine\ORM\EntityNotFoundException for the case, when template does not found
     */
    public function testExecuteWithoutTemplateEntity(): void
    {
        $this->expectException(\Doctrine\ORM\EntityNotFoundException::class);
        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->willThrowException(new EntityNotFoundException());

        $this->emailProcessor->expects($this->never())
            ->method('process');

        $this->sender->send(new \stdClass(), [new EmailAddressDTO('test@test.com')], 'test@test.com', 'test');
    }

    public function testExecuteWithProcessException(): void
    {
        $rcpt = new EmailAddressDTO('test@test.com');

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient($rcpt);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with([$rcpt], new EmailTemplateCriteria('test', \stdClass::class), ['entity' => new \stdClass()])
            ->willReturn([$dto]);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('plain/text');

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->once())
            ->method('getEmailOrigin')
            ->with('test@test.com', null)
            ->willReturn($emailOrigin);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(Email::class))
            ->willThrowException(new \Swift_SwiftException('The email was not delivered.'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Workflow send email template action.');

        $this->sender->send(new \stdClass(), [new EmailAddressDTO('test@test.com')], 'test@test.com', 'test');
    }

    /**
     * @dataProvider executeOptionsDataProvider
     *
     * @param array $options
     * @param string|object $recipient
     * @param array $expected
     */
    public function testExecute(array $options, $recipient, array $expected): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        if (!$recipient instanceof EmailHolderInterface) {
            $recipient = new EmailAddressDTO($recipient);
        }

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient(is_object($recipient) ? $recipient : new EmailAddressDTO($recipient));

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                [$recipient],
                new EmailTemplateCriteria('test', \stdClass::class),
                ['entity' => new \stdClass()]
            )
            ->willReturn([$dto]);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('plain/text');
        $this->emailTemplate->expects($this->once())
            ->method('getSubject')
            ->willReturn($expected['subject']);
        $this->emailTemplate->expects($this->once())
            ->method('getContent')
            ->willReturn($expected['body']);

        $emailEntity = $this->createMock(EmailEntity::class);

        $emailUserEntity = $this->createMock(EmailUser::class);
        $emailUserEntity->expects($this->any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->once())
            ->method('getEmailOrigin')
            ->with($expected['from'], null)
            ->willReturn($emailOrigin);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with(
                (new Email())
                    ->setFrom($expected['from'])
                    ->setSubject($expected['subject'])
                    ->setBody($expected['body'])
                    ->setTo($expected['to'])
                    ->setType('text'),
                $emailOrigin
            )
            ->willReturn($emailUserEntity);

        $this->sender->send(
            $options['entity'],
            [new EmailAddressDTO($options['to'])],
            $options['from'],
            $options['template']
        );
    }

    /**
     * @dataProvider executeOptionsDataProvider
     *
     * @param array $options
     * @param string|object $recipient
     * @param array $expected
     */
    public function testExecuteWithParamters(array $options, $recipient, array $expected): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        if (!$recipient instanceof EmailHolderInterface) {
            $recipient = new EmailAddressDTO($recipient);
        }

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient(is_object($recipient) ? $recipient : new EmailAddressDTO($recipient));

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                [$recipient],
                new EmailTemplateCriteria('test', \stdClass::class),
                ['entity' => new \stdClass(), 'param' => 'value']
            )
            ->willReturn([$dto]);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('plain/text');
        $this->emailTemplate->expects($this->once())
            ->method('getSubject')
            ->willReturn($expected['subject']);
        $this->emailTemplate->expects($this->once())
            ->method('getContent')
            ->willReturn($expected['body']);

        $emailEntity = $this->createMock(EmailEntity::class);

        $emailUserEntity = $this->createMock(EmailUser::class);
        $emailUserEntity->expects($this->any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->once())
            ->method('getEmailOrigin')
            ->with($expected['from'], null)
            ->willReturn($emailOrigin);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with(
                (new Email())
                    ->setFrom($expected['from'])
                    ->setSubject($expected['subject'])
                    ->setBody($expected['body'])
                    ->setTo($expected['to'])
                    ->setType('text'),
                $emailOrigin
            )
            ->willReturn($emailUserEntity);

        $this->sender->sendWithParameters(
            $options['entity'],
            [new EmailAddressDTO($options['to'])],
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
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                'test@test.com',
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'simple with name' => [
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"Test" <test@test.com>',
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ]
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

        $from = 'from@test.com';

        $rcpt1 = new EmailAddressDTO($toEmail1);
        $rcpt2 = new EmailAddressDTO($toEmail2);

        $dto1 = new LocalizedTemplateDTO($enTemplate);
        $dto1->addRecipient($rcpt1);
        $dto1->addRecipient($recipient1);

        $dto2 = new LocalizedTemplateDTO($deTemplate);
        $dto2->addRecipient($rcpt2);
        $dto2->addRecipient($recipient2);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                [$rcpt1, $rcpt2, $recipient1, $recipient2],
                new EmailTemplateCriteria('test', \stdClass::class),
                ['entity' => new \stdClass()]
            )
            ->willReturn([$dto1, $dto2]);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->exactly(2))
            ->method('getEmailOrigin')
            ->with($from, null)
            ->willReturn($emailOrigin);

        $this->emailProcessor->expects($this->exactly(2))
            ->method('process')
            ->withConsecutive(
                [
                    (new Email())
                        ->setFrom($from)
                        ->setSubject($enTemplate->getSubject())
                        ->setBody($enTemplate->getContent())
                        ->setTo([$toEmail1, $recipientEmail1])
                        ->setType('text'),
                    $emailOrigin,
                ],
                [
                    (new Email())
                        ->setFrom($from)
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
