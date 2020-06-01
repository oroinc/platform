<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Monolog;

use Doctrine\ORM\EntityRepository;
use Monolog\Logger;
use Oro\Bundle\LoggerBundle\Entity\LogEntry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\LoggerInterface;

class DbLogsHandlerTest extends WebTestCase
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var EntityRepository */
    protected $repo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->logger = $this->getContainer()->get('monolog.logger.oro_account_security');
        $this->repo = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(LogEntry::class);
    }

    /**
     * @dataProvider writeDataProvider
     * @param string $message
     * @param array $context
     * @param string $level
     * @param array $expected
     */
    public function testWrite($message, array $context, $level, array $expected)
    {
        $this->logger->$level($message, $context);

        /** @var LogEntry $logEntry */
        $logEntry = $this->repo->findOneBy([], ['id' => 'DESC']);
        self::assertInstanceOf(LogEntry::class, $logEntry);
        self::assertSame($expected['message'], $logEntry->getMessage());
        self::assertArrayIntersectEquals($expected['context'], $logEntry->getContext());
        self::assertSame(Logger::getLevels()[strtoupper($level)], $logEntry->getLevel());
        self::assertSame('oro_account_security', $logEntry->getChannel());
        self::assertInstanceOf(\DateTime::class, $logEntry->getDatetime());
        self::assertSame([], $logEntry->getExtra());
    }

    /**
     * @return array
     */
    public function writeDataProvider()
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
                    'context' => ['aaa' => 'bbb', 'ccc' => '[object] (stdClass: {})'],
                ]
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
