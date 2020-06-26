<?php

namespace Oro\Bundle\AssetBundle\Command;

use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Bundle\AssetBundle\NodeProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

/**
 * Run bin/webpack to build assets.
 * @SuppressWarnings(PHPMD)
 */
class OroAssetsBuildCommand extends Command
{
    /**
     * @see https://webpack.js.org/configuration/stats/#stats
     */
    protected const WEBPACK_VERBOSITY_MAP = [
        OutputInterface::VERBOSITY_QUIET => 'none',
        OutputInterface::VERBOSITY_NORMAL => false,
        OutputInterface::VERBOSITY_VERBOSE => 'normal',
        OutputInterface::VERBOSITY_VERY_VERBOSE => 'detailed',
        OutputInterface::VERBOSITY_DEBUG => 'verbose',
    ];

    /**
     * @see https://webpack.js.org/configuration/dev-server/#devserverstats-
     */
    protected const WEBPACK_DEV_SERVER_VERBOSITY_MAP = [
        OutputInterface::VERBOSITY_QUIET => 'none',
        OutputInterface::VERBOSITY_NORMAL => false,
        OutputInterface::VERBOSITY_DEBUG => 'verbose',
        OutputInterface::VERBOSITY_VERY_VERBOSE => 'normal',
        OutputInterface::VERBOSITY_VERBOSE => 'minimal',
    ];

    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'oro:assets:build';

    protected const BUILD_DIR = 'vendor/oro/platform/build/';

    /**
     * @var NodeProcessFactory
     */
    private $nodeProcessFactory;

    /**
     * @var AssetConfigCache
     */
    private $cache;

    /**
     * @var string
     */
    private $npmPath;

    /**
     * @var int|float|null
     */
    private $buildTimeout;

    /**
     * @var int|float|null
     */
    private $npmInstallTimeout;

    /**
     * @param NodeProcessFactory $nodeProcessFactory
     * @param AssetConfigCache   $cache
     * @param string             $npmPath
     * @param int|float|null     $buildTimeout
     * @param int|float|null     $npmInstallTimeout
     */
    public function __construct(
        NodeProcessFactory $nodeProcessFactory,
        AssetConfigCache $cache,
        string $npmPath,
        $buildTimeout,
        $npmInstallTimeout
    ) {
        $this->nodeProcessFactory = $nodeProcessFactory;
        $this->cache = $cache;
        $this->npmPath = $npmPath;
        $this->buildTimeout = $buildTimeout;
        $this->npmInstallTimeout = $npmInstallTimeout;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription(
                <<<DESCRIPTION
The command runs webpack to build assets.

In <comment>dev</comment> environment command builds assets without minification and with source-maps. 
In <comment>prod</comment> environment assets are minified and do not include source-maps.
 
<info>Note:</info> When using the <comment>watch</comment> mode after changing the assets configuration at 
<comment>assets.yml</comment> files, it is required to restart the command, otherwise it will not detect the changes. 
DESCRIPTION
            )
            ->addArgument(
                'theme',
                InputArgument::OPTIONAL,
                'Theme name to build. When not provided, all available themes are built.'
            )
            ->addOption(
                'hot',
                null,
                InputOption::VALUE_NONE,
                'Turn on hot module replacement. It allows all styles to be updated at runtime 
                without the need for a full refresh.'
            )
            ->addOption(
                'key',
                null,
                InputOption::VALUE_REQUIRED,
                'SSL Certificate key PEM file path. Used only with hot module replacement.'
            )
            ->addOption(
                'cert',
                null,
                InputOption::VALUE_REQUIRED,
                'SSL Certificate cert PEM file path. Used only with hot module replacement.'
            )
            ->addOption(
                'cacert',
                null,
                InputOption::VALUE_REQUIRED,
                'SSL Certificate cacert PEM file path. Used only with hot module replacement.'
            )
            ->addOption(
                'pfx',
                null,
                InputOption::VALUE_REQUIRED,
                'When used via the CLI, a path to an SSL .pfx file. '.
                'If used in options, it should be the bytestream of the .pfx file. '.
                'Used only with hot module replacement.'
            )
            ->addOption(
                'pfxPassphrase',
                null,
                InputOption::VALUE_REQUIRED,
                'The passphrase to a SSL PFX file. Used only with hot module replacement.'
            )
            ->addOption(
                'force-warmup',
                'f',
                InputOption::VALUE_NONE,
                'Warm up the asset-config.json cache.'
            )
            ->addOption(
                'watch',
                'w',
                InputOption::VALUE_NONE,
                'Turn on watch mode. This means that after the initial build, 
                webpack continues to watch the changes in any of the resolved files.'
            )
            ->addOption(
                'npm-install',
                'i',
                InputOption::VALUE_NONE,
                'Reinstall npm dependencies to vendor/oro/platform/build folder, to be used by webpack.'.
                'Required when "node_modules" folder is corrupted.'
            )
            ->addOption(
                'skip-css',
                null,
                InputOption::VALUE_NONE,
                'Skip build of CSS assets.'
            )
            ->addOption(
                'skip-js',
                null,
                InputOption::VALUE_NONE,
                'Skip build of JS assets.'
            )
            ->addOption(
                'skip-babel',
                null,
                InputOption::VALUE_NONE,
                'Skip transpiling code with babel.'
            )
            ->addOption(
                'skip-sourcemap',
                null,
                InputOption::VALUE_NONE,
                'Skip building source map.'
            )
            ->addUsage('admin.oro --watch')
            ->addUsage('blank -w')
            ->addUsage('blank --hot')
            ->addUsage('default')
            ->addUsage('-i');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getKernel();
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('force-warmup') || !$this->cache->exists($kernel->getCacheDir())) {
            $io->text('<info>Warming up the asset-config.json cache.</info>');
            $this->cache->warmUp($kernel->getCacheDir());
            $io->text('Done');
        }

        $nodeModulesDir = $kernel->getProjectDir().'/'.self::BUILD_DIR.'node_modules';
        if (!file_exists($nodeModulesDir) || $input->getOption('npm-install')) {
            $output->writeln('<info>Installing npm dependencies.</info>');
            $this->npmInstall($output);
        }

        $output->writeln('<info>Building assets.</info>');
        $this->buildAssets($input, $output);
        if (!$input->getOption('hot') && !$input->getOption('watch')) {
            $io->success('All assets were successfully built.');
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function buildAssets(InputInterface $input, OutputInterface $output): void
    {
        $buildTimeout = $this->buildTimeout;
        if ($input->getOption('hot')) {
            $buildTimeout = null;
        }
        $command = $this->buildCommand($input, $output);

        $process = $this->nodeProcessFactory->create(
            $command,
            $this->getKernel()->getProjectDir(),
            $buildTimeout
        );
        $output->writeln($process->getCommandLine());

        if ($input->getOption('watch')) {
            $process->setTimeout(null);
        }
        $this->handleSignals($process);

        $io = new SymfonyStyle($input, $output);
        $process->run(
            function ($type, $buffer) use ($io) {
                $io->write($buffer);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Build failed.');
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return array
     */
    protected function buildCommand(InputInterface $input, OutputInterface $output): array
    {
        if ($input->getOption('hot')) {
            $command[] = self::BUILD_DIR.'node_modules/webpack-dev-server/bin/webpack-dev-server.js';
            $command[] = '--hot';

            $this->mapSslOptions($input, $command);
        } else {
            $command[] = self::BUILD_DIR.'/node_modules/webpack/bin/webpack.js';
            $command[] = '--hide-modules';
        }

        if ($input->getArgument('theme')) {
            $command[] = '--env.theme='.$input->getArgument('theme');
        }
        if (true === $input->getOption('no-debug') || 'prod' === $input->getOption('env')) {
            $command[] = '--mode=production';
        }
        if ($input->getOption('watch')) {
            $command[] = '--watch';
        }

        // Handle the verbosity level. Options are different for the webpack and webpack-dev-server
        if ($input->getOption('hot')) {
            $verbosity = self::WEBPACK_DEV_SERVER_VERBOSITY_MAP[$output->getVerbosity()];
        } else {
            $verbosity = self::WEBPACK_VERBOSITY_MAP[$output->getVerbosity()];
        }
        $command[] = '--env.stats='.$verbosity;

        $command[] = '--env.symfony='.$input->getOption('env');
        $command[] = '--colors';

        if ($input->getOption('skip-css')) {
            $command[] = '--env.skipCSS';
        }
        if ($input->getOption('skip-js')) {
            $command[] = '--env.skipJS';
        }
        if ($input->getOption('skip-babel')) {
            $command[] = '--env.skipBabel';
        }
        if ($input->getOption('skip-sourcemap')) {
            $command[] = '--env.skipSourcemap';
        }

        return $command;
    }

    /**
     * @param InputInterface $input
     * @param array          $command
     */
    protected function mapSslOptions(InputInterface $input, array &$command): void
    {
        foreach (['key', 'cert', 'cacert', 'pfx', 'pfxPassphrase'] as $optionName) {
            $optionValue = $input->getOption($optionName);
            if ($optionValue) {
                $command[] = "--{$optionName}={$optionValue}";
            }
        }
    }

    /**
     * Handle exit signals, to make them processed by NodeJs
     *
     * @param Process $process
     */
    protected function handleSignals(Process $process): void
    {
        if (!\extension_loaded('pcntl')) {
            return;
        }
        $killNodeProcess = function () use ($process) {
            $process->signal(SIGKILL);
            exit();
        };
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, $killNodeProcess);
        pcntl_signal(SIGTERM, $killNodeProcess);
    }

    /**
     * @param OutputInterface $output
     */
    protected function npmInstall(OutputInterface $output): void
    {
        $command = [$this->npmPath, '--no-audit', 'install'];
        $output->writeln($command);
        $path = $this->getKernel()->getProjectDir().'/'.self::BUILD_DIR;
        $process = new Process($command, $path);
        $process->setTimeout($this->npmInstallTimeout);

        $process->run();

        if ($process->isSuccessful()) {
            $output->writeln('Done.');
        } else {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    /**
     * @return Kernel
     */
    private function getKernel(): Kernel
    {
        return $this->getApplication()->getKernel();
    }
}
