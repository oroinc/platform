<?php

declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\MigrationBundle\Locator\FixturePathLocator;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\TestBundle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LoadDataFixturesCommandTest extends TestCase
{
    private const string STUBS_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'Stubs';

    private DataFixturesLoader|MockObject $dataFixturesLoader;
    private LoadDataFixturesCommand $command;

    protected function setUp(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn(self::STUBS_DIR . DIRECTORY_SEPARATOR . 'application');

        $testBundle = new TestBundle('StubBundle');
        $testBundle->setPath(\implode(DIRECTORY_SEPARATOR, [self::STUBS_DIR, 'bundles', 'StubBundle']));

        $kernel->method('getBundles')->willReturn([$testBundle]);

        $this->dataFixturesLoader = $this->createMock(DataFixturesLoader::class);
        $this->dataFixturesLoader->method('getFixtures')->willReturn([]);

        $this->command = new LoadDataFixturesCommand(
            $kernel,
            $this->dataFixturesLoader,
            $this->createMock(DataFixturesExecutorInterface::class),
            new FixturePathLocator(),
            $this->createMock(ConfigManager::class),
            $this->createMock(ManagerRegistry::class)
        );

        $this->command->setHelperSet(new HelperSet(['question' => $this->createStub(QuestionHelper::class)]));
    }

    public function testExecuteWithoutNoBundlesOption(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturnMap([
            ['fixtures-type', DataFixturesExecutorInterface::DEMO_FIXTURES],
            ['no-bundles', null]
        ]);

        $this->dataFixturesLoader->expects(static::exactly(2))
            ->method('loadFromDirectory')
            ->withConsecutive(
                [\implode(DIRECTORY_SEPARATOR, [self::STUBS_DIR, 'bundles', 'StubBundle', 'Migrations/Data/Demo/ORM'])],
                [\implode(DIRECTORY_SEPARATOR, [self::STUBS_DIR, 'application', 'migrations/Stub/Data/Demo/ORM'])],
            );

        $this->command->run($input, $output);
    }

    public function testExecuteWithNoBundlesOption(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturnMap([
            ['fixtures-type', DataFixturesExecutorInterface::DEMO_FIXTURES],
            ['no-bundles', true]
        ]);

        $this->dataFixturesLoader->expects(static::once())
            ->method('loadFromDirectory')
            ->with(\implode(DIRECTORY_SEPARATOR, [self::STUBS_DIR, 'application', 'migrations/Stub/Data/Demo/ORM']));

        $this->command->run($input, $output);
    }
}
