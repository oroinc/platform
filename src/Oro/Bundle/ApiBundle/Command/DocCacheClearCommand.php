<?php
declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clears the API documentation cache.
 */
class DocCacheClearCommand extends Command
{
    private const ALL_VIEWS = 'all';

    /** @var string */
    protected static $defaultName = 'oro:api:doc:cache:clear';

    private CacheManager $cacheManager;
    /** @var string[] */
    private array $allApiDocViews;
    private string $environment;

    /**
     * @param CacheManager $cacheManager
     * @param string[] $allApiDocViews
     * @param string $environment
     */
    public function __construct(
        CacheManager $cacheManager,
        array $allApiDocViews,
        string $environment
    ) {
        parent::__construct();

        $this->cacheManager = $cacheManager;
        $this->allApiDocViews = $allApiDocViews;
        $this->environment = $environment;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function isEnabled(): bool
    {
        return $this->cacheManager->isApiDocCacheEnabled() && parent::isEnabled();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure(): void
    {
        $this
            ->addOption(
                'view',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'View name',
                [self::ALL_VIEWS]
            )
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.')
            ->setDescription('Clears the API documentation cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears the API documentation cache for all views.

  <info>php %command.full_name%</info>

The <info>--view</info> option can be used to clear the cache only for a specific view:

  <info>php %command.full_name% --view=rest_json_api</info>

The <info>--no-warmup</info> option can be used to skip warming up the cache after cleaning:

  <info>php %command.full_name% --no-warmup</info>

HELP
            )
            ->addUsage('--view=rest_json_api')
            ->addUsage('--no-warmup')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $views = $input->getOption('view');
        $noWarmup = $input->getOption('no-warmup');

        if (1 === \count($views) && self::ALL_VIEWS === reset($views)) {
            $views = $this->allApiDocViews;
        }

        // warm up API caches
        if (!$noWarmup) {
            $io->comment('Warming up API cache...');
            $this->cacheManager->warmUpCaches();
        }

        // process documentation cache
        foreach ($views as $view) {
            if ($noWarmup) {
                $io->comment(sprintf('Clearing the cache for the <info>%s</info> view...', $view));
                $this->cacheManager->clearApiDocCache($view);
            } else {
                $io->comment(sprintf('Warming up cache for the <info>%s</info> view...', $view));
                $this->cacheManager->warmUpApiDocCache($view);
            }
        }

        $io->success(sprintf(
            'API documentation cache was successfully cleared for "%s" environment.',
            $this->environment
        ));

        return 0;
    }
}
