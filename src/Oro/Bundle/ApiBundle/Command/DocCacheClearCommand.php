<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\ApiDoc\CachingApiDocExtractor;

class DocCacheClearCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:api:doc:cache:clear';

    const ALL_VIEWS                    = 'all';
    const API_DOC_VIEWS_PARAMETER_NAME = 'oro_api.api_doc.views';
    const API_DOC_EXTRACTOR_SERVICE    = 'nelmio_api_doc.extractor.api_doc_extractor';

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has(self::API_DOC_EXTRACTOR_SERVICE)) {
            return false;
        }
        $apiDocExtractor = $this->getContainer()->get(self::API_DOC_EXTRACTOR_SERVICE);
        if (!$apiDocExtractor instanceof CachingApiDocExtractor) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Clears API documentation cache')
            ->addOption(
                'view',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'A view for which API documentation cache should be cleared.',
                [self::ALL_VIEWS]
            )
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Do not warm up the cache.')
            ->setHelp(<<<EOF
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
        /** @var CachingApiDocExtractor $apiDocExtractor */
        $apiDocExtractor = $this->getContainer()->get(self::API_DOC_EXTRACTOR_SERVICE);

        if (1 === count($views) && self::ALL_VIEWS === reset($views)) {
            $views = $this->getContainer()->getParameter(self::API_DOC_VIEWS_PARAMETER_NAME);
        }

        // make sure API cache is up-to-date
        $cacheClearCommand = $this->getApplication()->find(CacheClearCommand::COMMAND_NAME);
        $cacheClearCommand->run(
            new ArrayInput(['command' => CacheClearCommand::COMMAND_NAME]),
            new NullOutput()
        );

        // process documentation cache
        foreach ($views as $view) {
            if ($noWarmup) {
                $io->comment(sprintf('Clearing the cache for the <info>%s</info> view', $view));
                $apiDocExtractor->clear($view);
            } else {
                $io->comment(sprintf('Warming up cache for the <info>%s</info> view...', $view));
                $apiDocExtractor->warmUp($view);
            }
        }

        $io->success('API documentation cache was successfully cleared.');
    }
}
