<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The CLI command to clear Data API documentation cache (ApiDoc cache).
 */
class DocCacheClearCommand extends ContainerAwareCommand
{
    public const COMMAND_NAME = 'oro:api:doc:cache:clear';

    private const ALL_VIEWS                    = 'all';
    private const API_DOC_VIEWS_PARAMETER_NAME = 'oro_api.api_doc.views';

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->getCacheManager()->isApiDocCacheEnabled() && parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Clears API documentation cache.')
            ->addOption(
                'view',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'A view for which API documentation cache should be cleared.',
                [self::ALL_VIEWS]
            )
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command clears API documentation cache for a given view:

  <info>php %command.full_name%</info>
  <info>php %command.full_name% --view=rest_json_api</info>

If <info>--view</info> option is not provided this command clears cache for all views.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $views = $input->getOption('view');
        $noWarmup = $input->getOption('no-warmup');
        $cacheManager = $this->getCacheManager();

        if (1 === count($views) && self::ALL_VIEWS === reset($views)) {
            $views = $this->getContainer()->getParameter(self::API_DOC_VIEWS_PARAMETER_NAME);
        }

        // warm up API caches
        if (!$noWarmup) {
            $io->comment('Warming up API cache...');
            $cacheManager->warmUpCaches();
        }

        // process documentation cache
        foreach ($views as $view) {
            if ($noWarmup) {
                $io->comment(sprintf('Clearing the cache for the <info>%s</info> view...', $view));
                $cacheManager->clearApiDocCache($view);
            } else {
                $io->comment(sprintf('Warming up cache for the <info>%s</info> view...', $view));
                $cacheManager->warmUpApiDocCache($view);
            }
        }

        $io->success(sprintf(
            'API documentation cache was successfully cleared for "%s" environment.',
            $this->getContainer()->get('kernel')->getEnvironment()
        ));
    }

    /**
     * @return CacheManager
     */
    private function getCacheManager()
    {
        return $this->getContainer()->get('oro_api.cache_manager');
    }
}
