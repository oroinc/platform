<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutor as BaseCommandExecutor;

class CommandExecutorTest extends \PHPUnit\Framework\TestCase
{
    private const NON_DEFAULT_OPTION_NAME = 'non_default_option_name';
    private const DEFAULT_OPTION_NAME = 'default_option_name';

    /** @var CommandExecutor */
    private $commandExecutor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|BaseCommandExecutor */
    private $baseCommandExecutor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OroDataCacheManager */
    private $dataCacheManager;

    protected function setUp()
    {
        $this->dataCacheManager = self::createMock(OroDataCacheManager::class);
        $this->baseCommandExecutor = self::createMock(BaseCommandExecutor::class);
        $this->commandExecutor = new CommandExecutor($this->baseCommandExecutor, $this->dataCacheManager);
    }

    public function testDefaultOption(): void
    {
        $this->baseCommandExecutor
            ->expects(self::exactly(3))
            ->method('getDefaultOption')
            ->willReturnOnConsecutiveCalls(BaseCommandExecutor::DEFAULT_TIMEOUT, null, true);

        $this->baseCommandExecutor
            ->expects(self::once())
            ->method('setDefaultOption')
            ->willReturnSelf();

        self::assertEquals(
            BaseCommandExecutor::DEFAULT_TIMEOUT,
            $this->commandExecutor->getDefaultOption('process-timeout')
        );

        self::assertNull($this->commandExecutor->getDefaultOption(self::NON_DEFAULT_OPTION_NAME));

        self::assertSame(
            $this->commandExecutor,
            $this->commandExecutor->setDefaultOption(self::DEFAULT_OPTION_NAME, true)
        );

        self::assertTrue($this->commandExecutor->getDefaultOption(self::DEFAULT_OPTION_NAME));
    }

    /**
     * @dataProvider runCommandProvider
     *
     * @param array $runCommand
     * @param array $cacheManager
     * @param int $expected
     */
    public function testRunCommand(array $runCommand, array $cacheManager, int $expected): void
    {
        $this->baseCommandExecutor
            ->expects($runCommand['expects'])
            ->method('runCommand')
            ->willReturn($runCommand['code']);

        $this->dataCacheManager
            ->expects($cacheManager['expects'])
            ->method('sync');

        $exitCode = $this->commandExecutor->runCommand('oro:acme', [
            '--disable-cache-sync' => $cacheManager['disableCacheSync'], null
        ]);

        self::assertEquals($expected, $exitCode);
    }

    /**
     * @return array
     */
    public function runCommandProvider(): array
    {
        return [
            'withoutCacheManager' => [
                'runCommand' => [
                    'code' => 1,
                    'expects' => self::once()
                ],
                'cacheManager' => [
                    'disableCacheSync' => false,
                    'expects' => self::once()
                ],
                'expected' => 1
            ],
            'withCacheManager' => [
                'runCommand' => [
                    'code' => 1,
                    'expects' => self::once()
                ],
                'cacheManager' => [
                    'disableCacheSync' => true,
                    'expects' => self::never()
                ],
                'expected' => 1
            ],
            'withZeroCode' => [
                'runCommand' => [
                    'code' => 0,
                    'expects' => self::once()
                ],
                'cacheManager' => [
                    'disableCacheSync' => false,
                    'expects' => self::once()
                ],
                'expected' => 0
            ]
        ];
    }
}
