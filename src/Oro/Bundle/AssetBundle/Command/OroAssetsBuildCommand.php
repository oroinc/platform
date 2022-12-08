<?php
declare(strict_types=1);

namespace Oro\Bundle\AssetBundle\Command;

use Oro\Bundle\AssetBundle\AssetCommandProcessFactory;
use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

/**
 * Runs webpack to build assets.
 */
class OroAssetsBuildCommand extends Command
{
    /** @see https://webpack.js.org/configuration/stats/#stats */
    protected const WEBPACK_VERBOSITY_MAP = [
        OutputInterface::VERBOSITY_QUIET => 'none',
        OutputInterface::VERBOSITY_NORMAL => false,
        OutputInterface::VERBOSITY_VERBOSE => 'normal',
        OutputInterface::VERBOSITY_VERY_VERBOSE => 'detailed',
        OutputInterface::VERBOSITY_DEBUG => 'verbose',
    ];

    /** @see https://webpack.js.org/configuration/dev-server/#devserverstats- */
    protected const WEBPACK_DEV_SERVER_VERBOSITY_MAP = [
        OutputInterface::VERBOSITY_QUIET => 'none',
        OutputInterface::VERBOSITY_NORMAL => false,
        OutputInterface::VERBOSITY_DEBUG => 'verbose',
        OutputInterface::VERBOSITY_VERY_VERBOSE => 'normal',
        OutputInterface::VERBOSITY_VERBOSE => 'minimal',
    ];

    protected static $defaultName = 'oro:assets:build';

    private AssetCommandProcessFactory $nodeProcessFactory;
    private AssetConfigCache $cache;

    private string $npmPath;

    /** @var int|float|null */
    private $buildTimeout;

    /** @var int|float|null */
    private $npmInstallTimeout;

    private bool $withBabel;

    /**
     * @param NodeProcessFactory $nodeProcessFactory
     * @param AssetConfigCache   $cache
     * @param string             $npmPath
     * @param int|float|null     $buildTimeout
     * @param int|float|null     $npmInstallTimeout
     * @param bool               $withBabel
     */
    public function __construct(
        AssetCommandProcessFactory $nodeProcessFactory,
        AssetConfigCache           $cache,
        string                     $npmPath,
        $buildTimeout,
        $npmInstallTimeout,
        bool $withBabel
    ) {
        $this->nodeProcessFactory = $nodeProcessFactory;
        $this->cache = $cache;
        $this->npmPath = $npmPath;
        $this->buildTimeout = $buildTimeout;
        $this->npmInstallTimeout = $npmInstallTimeout;
        $this->withBabel = $withBabel;

        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function configure()
    {
        $this
            ->addArgument('theme', InputArgument::OPTIONAL, 'Theme name')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Continue to watch for changes after initial build')
            ->addOption('hot', null, InputOption::VALUE_NONE, 'Turn on hot module replacement')
            ->addOption('key', null, InputOption::VALUE_REQUIRED, 'SSL certificate key PEM file path')
            ->addOption('cert', null, InputOption::VALUE_REQUIRED, 'SSL certificate PEM file path')
            ->addOption('cacert', null, InputOption::VALUE_REQUIRED, 'SSL certificate cacert PEM file path')
            ->addOption('pfx', null, InputOption::VALUE_REQUIRED, 'Path to SSL certificate .pfx file')
            ->addOption('pfxPassphrase', null, InputOption::VALUE_REQUIRED, 'Passphrase to the .pfx file')
            ->addOption('force-warmup', 'f', InputOption::VALUE_NONE, 'Warm up the asset-config.json cache')
            ->addOption('npm-install', 'i', InputOption::VALUE_NONE, 'Reinstall npm dependencies')
            ->addOption('skip-css', null, InputOption::VALUE_NONE, 'Skip build of CSS assets')
            ->addOption('skip-js', null, InputOption::VALUE_NONE, 'Skip build of JS assets')
            ->addOption('with-babel', null, InputOption::VALUE_NONE, 'Transpile code with Babel')
            ->addOption('skip-sourcemap', null, InputOption::VALUE_NONE, 'Skip building source maps')
            ->addOption('skip-rtl', null, InputOption::VALUE_NONE, 'Skip building RTL styles')
            ->addOption('analyze', null, InputOption::VALUE_NONE, 'Run BundleAnalyzerPlugin')
        ;
        $this
            ->setDescription('Runs webpack to build assets.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command runs bin/webpack to build the web assets in all themes.

  <info>php %command.full_name%</info>

The assets can be build only for a specific theme if its name is provided as an argument:

  <info>php %command.full_name% <theme-name></info>
  <info>php %command.full_name% default</info>
  <info>php %command.full_name% blank</info>
  <info>php %command.full_name% admin.oro</info>

With <info>--env=dev</info> the assets are built without minification and with source-maps,
while with <info>--env=prod</info> the assets are minified and do not include source-maps:

  <info>php %command.full_name% --env=dev</info>
  <info>php %command.full_name% --env=prod</info>

The <info>--watch</info> (<info>-w</info>) option can be used to continuously monitor all resolved files
and rebuild the necessary assets automatically when any changes are detected:

  <info>php %command.full_name% --watch</info>
  <info>php %command.full_name% -w</info>

<comment>Note:</comment> When using the <info>--watch</info> option you should restart the command after
you modify the assets configuration in <comment>assets.yml</comment> files, or it will not
be able to detect the changes otherwise.

The <info>--hot</info> option turns on the hot module replacement feature. It allows all styles
to be updated at runtime without the need for a full page refresh:

  <info>php %command.full_name% --hot</info>

The <info>--key</info>, <info>--cert</info>, <info>--cacert</info>, <info>--pfx</info> and <info>--pfxPassphrase</info> options can be used
with <info>--hot</info> option to allow the hot module replacement to work over HTTPS:

  <info>php %command.full_name% --hot --key=<path> --cert=<path> --cacert=<path> --pfx=<path> --pfxPassphrase=<passphrase></info>

The <info>--force-warmup</info> option can be used to warm up the <comment>asset-config.json</comment> cache:

  <info>php %command.full_name% --force-warmup</info>

The <info>--npm-install</info> option can be used to reinstall npm dependencies
in <comment>vendor/oro/platform/build</comment> folder. It may be required when
<comment>node_modules</comment> contents become corrupted:

  <info>php %command.full_name% --npm-install</info>

The <info>--skip-css</info>, <info>--skip-js</info>, <info>--with-babel</info>, <info>--skip-sourcemap</info> and <info>--skip-rtl</info> options allow to
skip building CSS and JavaScript files, and transpiling Javascript with Babel,
skip building sourcemaps and skip building RTL styles respectively:

  <info>php %command.full_name% --skip-css</info>
  <info>php %command.full_name% --skip-js</info>
  <info>php %command.full_name% --skip-sourcemap</info>
  <info>php %command.full_name% --skip-rtl</info>
  <info>php %command.full_name% --with-babel</info>

The <info>--analyze</info> option can be used to run BundleAnalyzerPlugin:

  <info>php %command.full_name% --analyze</info>

HELP
                // @codingStandardsIgnoreEnd
            );
        $this
            ->addUsage('<theme-name>')
            ->addUsage('default')
            ->addUsage('blank')
            ->addUsage('admin.oro')
            ->addUsage('--env=dev')
            ->addUsage('--env=prod')
            ->addUsage('--watch')
            ->addUsage('--watch admin.oro')
            ->addUsage('-w')
            ->addUsage('-w blank')
            ->addUsage('--hot')
            ->addUsage('--hot blank')
            ->addUsage('--hot --key=<path> --cert=<path> --cacert=<path> --pfx=<path> --pfxPassphrase=<passphrase>')
            ->addUsage('--force-warmup')
            ->addUsage('--npm-install')
            ->addUsage('--skip-css')
            ->addUsage('--skip-js')
            ->addUsage('--skip-sourcemap')
            ->addUsage('--skip-rtl')
            ->addUsage('--with-babel')
            ->addUsage('--analyze')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getKernel();
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('force-warmup') || !$this->cache->exists($kernel->getCacheDir())) {
            $io->text('<info>Warming up the asset-config.json cache.</info>');
            $this->cache->warmUp($kernel->getCacheDir());
            $io->text('Done');
        }

        $nodeModulesDir = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'node_modules';
        if (!file_exists($nodeModulesDir) || $input->getOption('npm-install')) {
            $output->writeln('<info>Installing npm dependencies.</info>');
            $this->npmInstall($output);
        }

        $output->writeln('<info>Building assets.</info>');
        $this->buildAssets($input, $output);
        if (!$input->getOption('hot') && !$input->getOption('watch')) {
            $io->success('All assets were successfully built.');
        }

        return 0;
    }

    /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function buildCommand(InputInterface $input, OutputInterface $output): array
    {
        $command= ['run', 'webpack', '--'];

        if ($input->getOption('hot')) {
            $command[] = '--hot';

            $this->mapSslOptions($input, $command);
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

        $command[] = '--env';
        $command[] = 'symfony='.$input->getOption('env');

        if ($verbosity) {
            $command[] = 'stats=' . $verbosity;
        }
        if ($input->getArgument('theme')) {
            $command[] = 'theme='.$input->getArgument('theme');
        }
        if ($input->getOption('skip-css')) {
            $command[] = 'skipCSS';
        }
        if ($input->getOption('skip-js')) {
            $command[] = 'skipJS';
        }
        if ($this->withBabel || $input->getOption('with-babel')) {
            $command[] = 'withBabel';
        }
        if ($input->getOption('skip-sourcemap')) {
            $command[] = 'skipSourcemap';
        }
        if ($input->getOption('skip-rtl')) {
            $command[] = 'skipRTL';
        }
        if ($input->getOption('analyze')) {
            $command[] = 'analyze';
        }

        return $command;
    }

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
        \pcntl_async_signals(true);
        \pcntl_signal(SIGINT, $killNodeProcess);
        \pcntl_signal(SIGTERM, $killNodeProcess);
    }

    protected function npmInstall(OutputInterface $output): void
    {
        $path = $this->getKernel()->getProjectDir();
        if (\file_exists($path . DIRECTORY_SEPARATOR . 'package-lock.json')) {
            $logLevel = $output->isVerbose() ? 'info' : 'error';
            $command = [$this->npmPath, 'ci', '--loglevel ' . $logLevel];
        } else {
            $command = [$this->npmPath, '--no-audit', 'install'];
        }
        $output->writeln(implode(' ', $command));
        $process = new Process($command, $path);
        $process->setTimeout($this->npmInstallTimeout);

        $process->run();

        if ($process->isSuccessful()) {
            $output->writeln('Done.');
        } else {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    private function getKernel(): Kernel
    {
        return $this->getApplication()->getKernel();
    }
}
