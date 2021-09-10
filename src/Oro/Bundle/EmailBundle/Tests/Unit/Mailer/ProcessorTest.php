<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBatchInterface;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\MimeTypesInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var DirectMailer|\PHPUnit\Framework\MockObject\MockObject */
    private $mailer;

    /** @var EmailEntityBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $emailEntityBuilder;

    /** @var EmailActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityManager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var UserEmailOrigin|\PHPUnit\Framework\MockObject\MockObject */
    private $userEmailOrigin;

    /** @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOriginHelper;

    /** @var \Swift_Transport_EsmtpTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $mailerTransport;

    /** @var MimeTypesInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypes;

    /** @var Processor */
    private $emailProcessor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->mailer = $this->createMock(DirectMailer::class);
        $this->emailEntityBuilder = $this->createMock(EmailEntityBuilder::class);
        $this->emailActivityManager = $this->createMock(EmailActivityManager::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->userEmailOrigin = $this->createMock(UserEmailOrigin::class);
        $this->emailOriginHelper = $this->getMockBuilder(EmailOriginHelper::class)
            ->onlyMethods(['setEmailModel', 'findEmailOrigin'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mailerTransport = $this->createMock(\Swift_Transport_EsmtpTransport::class);
        $this->mimeTypes = $this->createMock(MimeTypesInterface::class);

        $this->mailer->expects(self::any())
            ->method('getTransport')
            ->willReturn($this->mailerTransport);

        $this->userEmailOrigin->expects(self::any())
            ->method('getSmtpHost')
            ->willReturn('abc');
        $this->userEmailOrigin->expects(self::any())
            ->method('getSmtpPort')
            ->willReturn(25);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->with('OroEmailBundle:Email')
            ->willReturn($this->em);

        $folder = $this->createMock(EmailFolder::class);
        $this->userEmailOrigin->expects(self::any())
            ->method('getFolder')
            ->with(FolderType::SENT)
            ->willReturn($folder);

        $emailOriginRepo = $this->createMock(EntityRepository::class);
        $emailOriginRepo->expects(self::any())
            ->method('findOneBy')
            ->with(['internalName' => InternalEmailOrigin::BAP])
            ->willReturn($this->userEmailOrigin);
        $this->em->expects(self::any())
            ->method('getRepository')
            ->with('OroEmailBundle:InternalEmailOrigin')
            ->willReturn($emailOriginRepo);

        $this->emailProcessor = new Processor(
            $this->doctrineHelper,
            $this->mailer,
            new EmailAddressHelper(),
            $this->emailEntityBuilder,
            $this->emailActivityManager,
            $this->dispatcher,
            new DefaultCrypter(),
            $this->emailOriginHelper,
            $this->mimeTypes
        );
    }

    public function testProcessEmptyFromException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender can not be empty');

        $this->mailer->expects(self::never())
            ->method('createMessage');
        $this->mailer->expects(self::never())
            ->method('send');

        $this->emailProcessor->process($this->createEmailModel([]));
    }

    /**
     * @dataProvider invalidModelDataProvider
     */
    public function testProcessEmptyToException(array $data, string $exception, string $exceptionMessage): void
    {
        $this->mailer->expects(self::never())
            ->method('createMessage');
        $this->mailer->expects(self::never())
            ->method('send');

        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $this->emailProcessor->process($this->createEmailModel($data));
    }

    public function invalidModelDataProvider(): array
    {
        return [
            [[], \InvalidArgumentException::class, 'Sender can not be empty'],
            [['from' => 'test@test.com'], \InvalidArgumentException::class, 'Recipient can not be empty'],
        ];
    }

    public function testProcessSend(): void
    {
        $message = new \Swift_Message();
        $this->mailer->expects(self::once())
            ->method('send')
            ->with($message)
            ->willReturn(true);
        $emailOrigin = $this->userEmailOrigin;

        $oldMessageId = $message->getId();
        $this->emailProcessor->processSend($message, $emailOrigin);
        $messageId = $message->getId();

        self::assertEquals($oldMessageId, $messageId);
    }

    public function testProcessSendFailException(): void
    {
        $this->expectException(\Swift_SwiftException::class);
        $this->expectExceptionMessage('The email was not delivered.');

        $message = $this->getMockForAbstractClass(\Swift_Message::class);
        $this->mailer->expects(self::once())
            ->method('createMessage')
            ->willReturn($message);
        $this->mailer->expects(self::once())
            ->method('send')
            ->with($message)
            ->willReturn(false);

        $model = $this->createEmailModel(
            [
                'from'    => 'test@test.com',
                'to'      => ['test2@test.com'],
                'subject' => 'test',
                'body'    => 'test body'
            ]
        );
        $this->emailProcessor->process($model);
    }

    public function testProcessAddressException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The $addresses argument must be a string or a list of strings (array or Iterator)'
        );

        $message = $this->getMockForAbstractClass(\Swift_Message::class);
        $this->mailer->expects(self::once())
            ->method('createMessage')
            ->willReturn($message);
        $this->mailer->expects(self::never())
            ->method('send');

        $model = $this->createEmailModel(
            [
                'from' => new \stdClass(),
                'to' => [new \stdClass()],
            ]
        );
        $this->emailProcessor->process($model);
    }

    /**
     * @dataProvider messageDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess(array $data, array $expectedMessageData, bool $needConverting = false): void
    {
        $message = new \Swift_Message();
        $this->mailer->expects(self::once())
            ->method('createMessage')
            ->willReturn($message);
        $this->mailer->expects(self::once())
            ->method('send')
            ->with($message)
            ->willReturn(true);

        $oldMessageId = $message->getId();

        $emailUser = $this->getMockBuilder(EmailUser::class)
            ->onlyMethods(['addFolder', 'getEmail'])
            ->getMock();
        $email = $this->createMock(Email::class);
        $emailUser->expects(self::any())
            ->method('getEmail')
            ->willReturn($email);
        $this->emailEntityBuilder->expects(self::once())
            ->method('emailUser')
            ->with(
                $data['subject'],
                $data['from'],
                $data['to'],
                $this->isInstanceOf('DateTime'),
                $this->isInstanceOf('DateTime'),
                $this->isInstanceOf('DateTime'),
                Email::NORMAL_IMPORTANCE,
                $data['cc'],
                $data['bcc']
            )
            ->willReturn($emailUser);

        $body = $this->createMock(EmailBody::class);
        $this->emailEntityBuilder->expects(self::once())
            ->method('body')
            ->willReturn($body);

        $batch = $this->createMock(EmailEntityBatchInterface::class);
        $this->emailEntityBuilder->expects(self::once())
            ->method('getBatch')
            ->willReturn($batch);
        $batch->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($this->em));
        $this->em->expects(self::once())
            ->method('flush');

        $email->expects(self::any())
            ->method('getEmailBody')
            ->willReturn($body);

        if (!empty($data['entityClass'])) {
            $targetEntity = new TestUser();
            $this->doctrineHelper->expects(self::exactly(0))
                ->method('getEntity')
                ->with($data['entityClass'], $data['entityId'])
                ->willReturn($targetEntity);
            $this->emailActivityManager->expects(self::exactly(0))
                ->method('addAssociation')
                ->with($this->identicalTo($email), $this->identicalTo($targetEntity));
        }

        $model = $this->createEmailModel($data);

        if ($needConverting) {
            $this->mimeTypes->expects(self::once())
                ->method('getExtensions')
                ->with('image/png')
                ->willReturn(['png']);
        }

        self::assertSame($emailUser, $this->emailProcessor->process($model));
        self::assertEquals($expectedMessageData['from'], [$model->getFrom()]);
        self::assertEquals($data['cc'], $model->getCc());
        self::assertEquals($data['bcc'], $model->getBcc());
        self::assertEquals($expectedMessageData['subject'], $model->getSubject());
        self::assertEquals($oldMessageId, $message->getId());

        if ($needConverting) {
            $id = $model->getAttachments()->first()->getEmailAttachment()->getEmbeddedContentId();
            self::assertEquals(sprintf($expectedMessageData['body'], 'cid:' . $id), $message->getBody());
        } else {
            self::assertEquals($expectedMessageData['body'], $model->getBody());
            self::assertEquals($expectedMessageData['body'], $message->getBody());
        }
    }

    /**
    * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
    */
    public function messageDataProvider(): array
    {
        return [
            [
                [
                    'from' => 'from@test.com',
                    'to' => ['to@test.com'],
                    'cc' => ['Cc <cc@test.com>'],
                    'bcc' => ['Bcc <bcc@test.com>'],
                    'subject' => 'subject',
                    'body' => 'body <img width=100 src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACAQMAAAB'
                            .'IeJ9nAAAAA1BMVEX///+nxBvIAAAAAWJLR0QAiAUdSAAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB98GEA'
                            .'grLyNXN+0AAAAmaVRYdENvbW1lbnQAAAAAAENyZWF0ZWQgd2l0aCBHSU1QIG9uIGEgTWFjleRfWwAAAAxJREFUCN'
                            .'djYGBgAAAABAABJzQnCgAAAABJRU5ErkJggg==" height="100"/>',
                    'type' => 'html'
                ],
                [
                    'from' => ['from@test.com'],
                    'to' => ['to@test.com'],
                    'cc' => ['cc@test.com' => 'Cc'],
                    'bcc' => ['bcc@test.com' => 'Bcc'],
                    'subject' => 'subject',
                    'body' => 'body <img width=100 src="%s" height="100"/>',
                    'type' => 'text/html'
                ],
                true
            ],
            [
                [
                    'from' => 'from@test.com',
                    'to' => ['to@test.com'],
                    'cc' => ['Cc <cc@test.com>'],
                    'bcc' => ['Bcc <bcc@test.com>'],
                    'subject' => 'subject',
                    'body' => 'body <img src="http://sth.com/cool-image.png">',
                    'type' => 'html'
                ],
                [
                    'from' => ['from@test.com'],
                    'to' => ['to@test.com'],
                    'cc' => ['cc@test.com' => 'Cc'],
                    'bcc' => ['bcc@test.com' => 'Bcc'],
                    'subject' => 'subject',
                    'body' => 'body <img src="http://sth.com/cool-image.png">',
                    'type' => 'text/html'
                ],
            ],
            [
                [
                    'from' => 'from@test.com',
                    'to' => ['to@test.com'],
                    'cc' => [],
                    'bcc' => [],
                    'subject' => 'subject',
                    'body' => 'body',
                    'type' => 'html'
                ],
                [
                    'from' => ['from@test.com'],
                    'to' => ['to@test.com'],
                    'cc' => [],
                    'bcc' => [],
                    'subject' => 'subject',
                    'body' => 'body',
                    'type' => 'text/html'
                ]
            ],
            [
                [
                    'from' => 'Test <from@test.com>',
                    'to' => ['To <to@test.com>', 'to2@test.com'],
                    'cc' => ['Cc3 <cc3@test.com>', 'cc4@test.com'],
                    'bcc' => [],
                    'subject' => 'subject',
                    'body' => 'body'
                ],
                [
                    'from' => ['Test <from@test.com>'],
                    'to' => ['to@test.com' => 'To', 'to2@test.com'],
                    'cc' => ['cc3@test.com' => 'Cc3', 'cc4@test.com'],
                    'bcc' => [],
                    'subject' => 'subject',
                    'body' => 'body'
                ]
            ],
            [
                [
                    'from' => 'from@test.com',
                    'to' => ['to1@test.com', 'to1@test.com', 'to2@test.com'],
                    'cc' => [],
                    'bcc' => ['bcc3@test.com', 'bcc4@test.com'],
                    'subject' => 'subject',
                    'body' => 'body',
                    'entityClass' => 'Entity\Target',
                    'entityId' => 123
                ],
                [
                    'from' => ['from@test.com'],
                    'to' => ['to1@test.com', 'to1@test.com', 'to2@test.com'],
                    'cc' => [],
                    'bcc' => ['bcc3@test.com', 'bcc4@test.com'],
                    'subject' => 'subject',
                    'body' => 'body'
                ]
            ],
        ];
    }

    private function createEmailModel($data): EmailModel
    {
        $email = new EmailModel();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $key => $value) {
            $propertyAccessor->setValue($email, $key, $value);
        }
        return $email;
    }
}
