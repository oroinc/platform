<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;

class CommandExecutorTest extends \PHPUnit_Framework_TestCase
{
    private const ENV = 'dev';
    private const CONSOLE_CMD_PATH = '-r';
    private const NON_DEFAULT_OPTION_NAME = 'non_default_option_name';
    private const DEFAULT_OPTION_NAME = 'default_option_name';

    /** @var \PHPUnit_Framework_MockObject_MockObject|CommandExecutor */
    private $commandExecutor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OroDataCacheManager */
    private $dataCacheManager;

    protected function setUp()
    {
        $this->dataCacheManager = self::createMock(OroDataCacheManager::class);

        $this->commandExecutor = $this->getMockBuilder(CommandExecutor::class)
            ->setConstructorArgs([self::CONSOLE_CMD_PATH, self::ENV, $this->dataCacheManager])
            ->setMethods(['processResult'])
            ->getMock();
    }

    public function testDefaultOption(): void
    {
        self::assertEquals(
            CommandExecutor::DEFAULT_TIMEOUT,
            $this->commandExecutor->getDefaultOption('process-timeout')
        );

        self::assertNull($this->commandExecutor->getDefaultOption(self::NON_DEFAULT_OPTION_NAME));

        self::assertEquals(
            $this->commandExecutor,
            $this->commandExecutor->setDefaultOption(self::DEFAULT_OPTION_NAME, true)
        );

        self::assertTrue($this->commandExecutor->getDefaultOption(self::DEFAULT_OPTION_NAME));
    }

    public function testDefaultTimeout()
    {
        $this->commandExecutor->setDefaultTimeout(30);
        self::assertEquals(30, $this->commandExecutor->getDefaultTimeout());
        self::assertEquals(30, $this->commandExecutor->getDefaultOption('process-timeout'));
    }

    /**
     * @dataProvider runCommandProvider
     *
     * @param bool $disableCacheSync
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expected
     */
    public function testRunCommandWithCacheSync(
        bool $disableCacheSync,
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expected
    ): void {
        $this->dataCacheManager
            ->expects($expected)
            ->method('sync');

        $this->commandExecutor->runCommand('oro:acme', [
            '--disable-cache-sync' => $disableCacheSync, null
        ]);
    }

    /**
     * @return array
     */
    public function runCommandProvider(): array
    {
        return [
            'withoutCacheManager' => [
                'disableCacheSync' => false,
                'expected' => self::once()
            ],
            'withCacheManager' => [
                'disableCacheSync' => true,
                'expected' => self::never()
            ]
        ];
    }
}
