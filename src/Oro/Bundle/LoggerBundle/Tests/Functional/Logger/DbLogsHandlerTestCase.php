<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Logger;

use Doctrine\ORM\EntityRepository;
use Monolog\Logger;
use Oro\Bundle\LoggerBundle\Entity\LogEntry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\LoggerInterface;

abstract class DbLogsHandlerTestCase extends WebTestCase
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityRepository */
    private $repo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->logger = $this->getContainer()->get('monolog.logger.' . $this->getLogChannelName());
        $this->repo = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(LogEntry::class);
    }

    abstract protected function getLogChannelName(): string;

    /**
     * @dataProvider writeDataProvider
     */
    public function testWrite(string $message, array $context, string $level, array $expected)
    {
        $this->logger->$level($message, $context);

        /** @var LogEntry $logEntry */
        $logEntry = $this->repo->findOneBy([], ['id' => 'DESC']);
        self::assertInstanceOf(LogEntry::class, $logEntry);
        self::assertSame($expected['message'], $logEntry->getMessage());
        self::assertArrayIntersectEquals($expected['context'], $logEntry->getContext());
        self::assertSame(Logger::getLevels()[strtoupper($level)], $logEntry->getLevel());
        self::assertSame($this->getLogChannelName(), $logEntry->getChannel());
        self::assertInstanceOf(\DateTime::class, $logEntry->getDatetime());
        self::assertSame([], $logEntry->getExtra());
    }

    public function writeDataProvider(): array
    {
        return [
            [
                'message' => 'first error msg',
                'context' => [],
                'level' => 'info',
                'expected' => [
                    'message' => 'first error msg',
                    'context' => [],
                ]
            ],
            [
                'message' => 'second error msg',
                'context' => ['aaa' => 'bbb', 'ccc' => new \stdClass()],
                'level' => 'warning',
                'expected' => [
                    'message' => 'second error msg',
                    'context' => ['aaa' => 'bbb', 'ccc' => ['stdClass' => []]],
                ],
            ],
            [
                'message' => 'third error msg',
                'context' => ['exception' => new \Exception('some exception message', 321)],
                'level' => 'critical',
                'expected' => [
                    'message' => 'third error msg',
                    'context' => [
                        'exception' => [
                            'class' => 'Exception',
                            'message' => 'some exception message',
                            'code' => 321
                        ]
                    ],
                ]
            ],
        ];
    }
}
