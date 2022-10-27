<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserLoginAttempt;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Oro\Bundle\UserBundle\Security\UserLoginAttemptLogger;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserLoginAttemptLoggerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var UserLoggingInfoProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userInfoProvider;

    /** @var BufferingLogger */
    private $logger;

    /** @var UserLoginAttemptLogger */
    private $attemptlogger;

    protected function setUp(): void
    {
        $this->userInfoProvider = $this->createMock(UserLoggingInfoProviderInterface::class);
        $this->logger = new BufferingLogger();
        $attemptClass = UserLoginAttempt::class;
        $loginSources = [
            'default'       => ['label' => 'default_label', 'code' => 1],
            'impersonation' => ['label' => 'impersonation_label', 'code' => 10],
        ];

        $this->em = $this->createMock(EntityManager::class);
        $this->connection = $this->createMock(Connection::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with($attemptClass)
            ->willReturn($this->em);
        $this->em->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->with($attemptClass)
            ->willReturn($this->mockMetadata());

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function (string $string) {
                return '_translated.' . $string;
            });

        $this->attemptlogger = new UserLoginAttemptLogger(
            $doctrine,
            $this->userInfoProvider,
            $translator,
            $this->logger,
            $attemptClass,
            $loginSources
        );
    }

    private function mockMetadata(): ClassMetadata
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())
            ->method('getTableName')
            ->willReturn('oro_user_login');

        return $metadata;
    }

    public function testLogSuccessLoginAttemptWithStringUserAndUserAgent(): void
    {
        $user = 'john';
        $source = 'default';
        $userAgentString = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/98.0.4758.109 Safari/537.36';

        $this->userInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn([
                'user' => $user,
                'ip' => '127.0.0.1',
                'user agent' => $userAgentString
            ]);

        $this->connection->expects(self::once())
            ->method('insert')
            ->with(
                'oro_user_login',
                self::isType('array'),
                [
                    'id'         => 'guid',
                    'success'    => 'boolean',
                    'source'     => 'integer',
                    'username'   => 'text',
                    'attempt_at' => 'datetime',
                    'context'    => 'json',
                    'user_agent' => 'text'
                ]
            )
            ->willReturnCallback(function (string $table, array $data) use ($userAgentString) {
                self::assertIsString($data['id']);
                self::assertTrue($data['success']);
                self::assertEquals(1, $data['source']);
                self::assertEquals('john', $data['username']);
                self::assertInstanceOf(\DateTime::class, $data['attempt_at']);
                self::assertEquals($userAgentString, $data['user_agent']);
                self::assertEquals(
                    ['user' => 'john', 'ip' => '127.0.0.1'],
                    $data['context']
                );

                return 1;
            });

        $this->attemptlogger->logSuccessLoginAttempt($user, $source);

        [$logLevel, $message, $context] = $this->logger->cleanLogs()[0];
        self::assertEquals('notice', $logLevel);
        self::assertEquals('Success login attempt.', $message);
        self::assertIsString($context['id']);
        self::assertTrue($context['success']);
        self::assertEquals(1, $context['source']);
        self::assertEquals($userAgentString, $context['user_agent']);
        self::assertEquals('john', $context['username']);
        self::assertInstanceOf(\DateTime::class, $context['attempt_at']);
        self::assertEquals(
            ['user' => 'john', 'ip' => '127.0.0.1'],
            $context['context']
        );
    }

    public function testLogSuccessLoginAttemptWithStringUserAndAdditionalContext(): void
    {
        $user = 'john';
        $source = 'default';

        $this->userInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn(['user' => $user, 'ip' => '127.0.0.1']);

        $this->connection->expects(self::once())
            ->method('insert')
            ->with(
                'oro_user_login',
                self::isType('array'),
                [
                    'id'         => 'guid',
                    'success'    => 'boolean',
                    'source'     => 'integer',
                    'username'   => 'text',
                    'attempt_at' => 'datetime',
                    'context'    => 'json'
                ]
            )
            ->willReturnCallback(function (string $table, array $data) {
                self::assertIsString($data['id']);
                self::assertTrue($data['success']);
                self::assertEquals(1, $data['source']);
                self::assertEquals('john', $data['username']);
                self::assertInstanceOf(\DateTime::class, $data['attempt_at']);
                self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1', 'additional' => 'value'], $data['context']);

                return 1;
            });

        $this->attemptlogger->logSuccessLoginAttempt($user, $source, ['additional' => 'value']);

        [$logLevel, $message, $context] = $this->logger->cleanLogs()[0];
        self::assertEquals('notice', $logLevel);
        self::assertEquals('Success login attempt.', $message);
        self::assertIsString($context['id']);
        self::assertTrue($context['success']);
        self::assertEquals(1, $context['source']);
        self::assertEquals('john', $context['username']);
        self::assertInstanceOf(\DateTime::class, $context['attempt_at']);
        self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1', 'additional' => 'value'], $context['context']);
    }

    public function testLogSuccessLoginAttemptWithStringUserAndNotDefaultSource(): void
    {
        $user = 'john';
        $source = 'impersonation';

        $this->userInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn(['user' => $user, 'ip' => '127.0.0.1']);

        $this->connection->expects(self::once())
            ->method('insert')
            ->with(
                'oro_user_login',
                self::isType('array'),
                [
                    'id'         => 'guid',
                    'success'    => 'boolean',
                    'source'     => 'integer',
                    'username'   => 'text',
                    'attempt_at' => 'datetime',
                    'context'    => 'json'
                ]
            )
            ->willReturnCallback(function (string $table, array $data) {
                self::assertIsString($data['id']);
                self::assertTrue($data['success']);
                self::assertEquals(10, $data['source']);
                self::assertEquals('john', $data['username']);
                self::assertInstanceOf(\DateTime::class, $data['attempt_at']);
                self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1'], $data['context']);

                return 1;
            });

        $this->attemptlogger->logSuccessLoginAttempt($user, $source);

        [$logLevel, $message, $context] = $this->logger->cleanLogs()[0];
        self::assertEquals('notice', $logLevel);
        self::assertEquals('Success login attempt.', $message);
        self::assertIsString($context['id']);
        self::assertTrue($context['success']);
        self::assertEquals(10, $context['source']);
        self::assertEquals('john', $context['username']);
        self::assertInstanceOf(\DateTime::class, $context['attempt_at']);
        self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1'], $context['context']);
    }

    public function testLogSuccessLoginAttemptWithStringUserAndNotSupportedSource(): void
    {
        $user = 'john';
        $source = 'other_not_supported';

        $this->userInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn(['user' => $user, 'ip' => '127.0.0.1']);

        $this->connection->expects(self::once())
            ->method('insert')
            ->with(
                'oro_user_login',
                self::isType('array'),
                [
                    'id'         => 'guid',
                    'success'    => 'boolean',
                    'source'     => 'integer',
                    'username'   => 'text',
                    'attempt_at' => 'datetime',
                    'context'    => 'json'
                ]
            )
            ->willReturnCallback(function (string $table, array $data) {
                self::assertIsString($data['id']);
                self::assertTrue($data['success']);
                self::assertEquals(1, $data['source']);
                self::assertEquals('john', $data['username']);
                self::assertInstanceOf(\DateTime::class, $data['attempt_at']);
                self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1'], $data['context']);

                return 1;
            });

        $this->attemptlogger->logSuccessLoginAttempt($user, $source);

        [$logLevel, $message, $context] = $this->logger->cleanLogs()[0];
        self::assertEquals('notice', $logLevel);
        self::assertEquals('Success login attempt.', $message);
        self::assertIsString($context['id']);
        self::assertTrue($context['success']);
        self::assertEquals(1, $context['source']);
        self::assertEquals('john', $context['username']);
        self::assertInstanceOf(\DateTime::class, $context['attempt_at']);
        self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1'], $context['context']);
    }

    public function testLogSuccessLoginAttemptWithObjectUser(): void
    {
        $user = new User();
        $user->setUsername('objectUserName');
        $user->setId(123);
        $source = 'other_not_supported';

        $this->userInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn(['userid' => 123, 'ip' => '127.0.0.1']);

        $this->connection->expects(self::once())
            ->method('insert')
            ->with(
                'oro_user_login',
                self::isType('array'),
                [
                    'id'         => 'guid',
                    'success'    => 'boolean',
                    'source'     => 'integer',
                    'username'   => 'text',
                    'attempt_at' => 'datetime',
                    'context'    => 'json',
                    'user_id'    => 'integer',
                ]
            )
            ->willReturnCallback(function (string $table, array $data) {
                self::assertIsString($data['id']);
                self::assertTrue($data['success']);
                self::assertEquals(1, $data['source']);
                self::assertEquals('objectUserName', $data['username']);
                self::assertInstanceOf(\DateTime::class, $data['attempt_at']);
                self::assertEquals(['userid' => 123, 'ip' => '127.0.0.1'], $data['context']);
                self::assertEquals(123, $data['user_id']);

                return 1;
            });

        $this->attemptlogger->logSuccessLoginAttempt($user, $source);

        [$logLevel, $message, $context] = $this->logger->cleanLogs()[0];
        self::assertEquals('notice', $logLevel);
        self::assertEquals('Success login attempt.', $message);
        self::assertIsString($context['id']);
        self::assertTrue($context['success']);
        self::assertEquals(1, $context['source']);
        self::assertEquals('objectUserName', $context['username']);
        self::assertInstanceOf(\DateTime::class, $context['attempt_at']);
        self::assertEquals(['userid' => 123, 'ip' => '127.0.0.1'], $context['context']);
        self::assertEquals(123, $context['user_id']);
    }

    public function testLogFailedLoginAttemptWithStringUser(): void
    {
        $user = 'john';
        $source = 'default';

        $this->userInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn(['user' => $user, 'ip' => '127.0.0.1']);

        $this->connection->expects(self::once())
            ->method('insert')
            ->with(
                'oro_user_login',
                self::isType('array'),
                [
                    'id'         => 'guid',
                    'success'    => 'boolean',
                    'source'     => 'integer',
                    'username'   => 'text',
                    'attempt_at' => 'datetime',
                    'context'    => 'json'
                ]
            )
            ->willReturnCallback(function (string $table, array $data) {
                self::assertIsString($data['id']);
                self::assertFalse($data['success']);
                self::assertEquals(1, $data['source']);
                self::assertEquals('john', $data['username']);
                self::assertInstanceOf(\DateTime::class, $data['attempt_at']);
                self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1'], $data['context']);

                return 1;
            });

        $this->attemptlogger->logFailedLoginAttempt($user, $source);

        [$logLevel, $message, $context] = $this->logger->cleanLogs()[0];
        self::assertEquals('notice', $logLevel);
        self::assertEquals('Failed login attempt.', $message);
        self::assertIsString($context['id']);
        self::assertFalse($context['success']);
        self::assertEquals(1, $context['source']);
        self::assertEquals('john', $context['username']);
        self::assertInstanceOf(\DateTime::class, $context['attempt_at']);
        self::assertEquals(['user' => 'john', 'ip' => '127.0.0.1'], $context['context']);
    }

    public function testLogSuccessLoginAttemptWithExceptionDuringSavingToDb(): void
    {
        $user = 'john';
        $source = 'default';
        $exception = new \Exception('Exception during saving');

        $this->userInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn(['user' => $user, 'ip' => '127.0.0.1']);

        $this->connection->expects(self::once())
            ->method('insert')
            ->willThrowException($exception);

        $this->attemptlogger->logSuccessLoginAttempt($user, $source);

        $logs = $this->logger->cleanLogs();
        [$logLevel, $message, $context] = $logs[0];
        self::assertEquals('error', $logLevel);
        self::assertEquals('Cannot save user attempt log item.', $message);
        self::assertEquals(['exception' => $exception], $context);

        [$logLevel, $message, ] = $logs[1];
        self::assertEquals('notice', $logLevel);
        self::assertEquals('Success login attempt.', $message);
    }

    public function testGetSourceChoices()
    {
        self::assertEquals(
            [
                '_translated.default_label' => 1,
                '_translated.impersonation_label' => 10,
            ],
            $this->attemptlogger->getSourceChoices()
        );
    }
}
