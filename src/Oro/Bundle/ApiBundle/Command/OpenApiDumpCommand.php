<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Renderer\OpenApiRenderer;
use Oro\Bundle\ApiBundle\Provider\OpenApiChoicesProvider;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * Dumps API documentation in OpenAPI format.
 */
class OpenApiDumpCommand extends Command
{
    protected static $defaultName = 'oro:api:doc:open-api:dump';
    protected static $defaultDescription = 'Dumps API documentation in OpenAPI format.';

    private OpenApiRenderer $openApiRenderer;
    private OpenApiChoicesProvider $openApiChoicesProvider;

    public function __construct(OpenApiRenderer $openApiRenderer, OpenApiChoicesProvider $openApiChoicesProvider)
    {
        $this->openApiRenderer = $openApiRenderer;
        $this->openApiChoicesProvider = $openApiChoicesProvider;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'view',
                null,
                InputOption::VALUE_REQUIRED,
                'The view to dump. Possible values: ' . implode(', ', $this->getAvailableViews())
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'The output format. Possible values: ' . implode(', ', $this->openApiRenderer->getAvailableFormats()),
                'json'
            )
            ->addOption(
                'entity',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Entities for which API documentation should be dumped'
            )
            ->addOption('title', '', InputOption::VALUE_REQUIRED, 'The title of the specification')
            ->addOption('no-validation', '', InputOption::VALUE_NONE, 'Skip validation of the specification')
            ->addOption('server-url', '', InputOption::VALUE_REQUIRED, 'The URL where live API is served')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps API documentation in OpenAPI format.

  <info>php %command.full_name% --view=<view></info>

By default, OpenAPI specification is dumped in JSON. To dump it in another format, use the "--format" option:

  <info>php %command.full_name% --view=<view> --format=json-pretty</info>
  <info>php %command.full_name% --view=<view> --format=yaml</info>

To skip validation of the generated OpenAPI specification, use the "--no-validation" option:

  <info>php %command.full_name% --view=<view> --no-validation</info>

To generate OpenAPI specification only for specified entities, use the "--entity" option:

  <info>php %command.full_name% --view=<view> --entity=entity1 --entity=entity2</info>

To provide a title of the OpenAPI specification, use the "--title" option:

  <info>php %command.full_name% --view=<view> --title="My API"</info>

To provide a URL where live API is served, use the "--server-url" option:

  <info>php %command.full_name% --view=<view> --server-url=https://example.com</info>

HELP
            )
            ->addUsage('--view <view>')
            ->addUsage('--view <view> --format <format>')
            ->addUsage('--view <view> --no-validation')
            ->addUsage('--view <view> --entity <entity>')
            ->addUsage('--view <view> --title <title>')
            ->addUsage('--view <view> --server-url <url>')
        ;

        parent::configure();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $view = $this->getView($input);
        $format = $this->getFormat($input);
        $options = $this->getOptions($input);

        $hasErrors = false;
        $bufferingLogger = new BufferingLogger();
        $options['logger'] = $bufferingLogger;
        try {
            $output->writeln(
                $this->openApiRenderer->render($view, $format, $options),
                OutputInterface::OUTPUT_RAW
            );
        } finally {
            $logs = $bufferingLogger->cleanLogs();
            if ($logs) {
                $consoleLogger = new ConsoleLogger($output, [], [LogLevel::WARNING => ConsoleLogger::ERROR]);
                $errorLevels = [
                    LogLevel::WARNING,
                    LogLevel::ERROR,
                    LogLevel::CRITICAL,
                    LogLevel::ALERT,
                    LogLevel::EMERGENCY
                ];
                foreach ($logs as [$level, $message, $context]) {
                    $consoleLogger->log($level, $message, $context);
                    if (!$hasErrors && \in_array($level, $errorLevels, true)) {
                        $hasErrors = true;
                    }
                }
            }
        }

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }

    private function getView(InputInterface $input): string
    {
        $view = $input->getOption('view');
        if (!$view) {
            throw new InvalidArgumentException('The "--view" option is missing.');
        }
        if (!\in_array($view, $this->openApiRenderer->getAvailableViews(), true)) {
            throw new InvalidArgumentException(sprintf(
                'The specified view does not exist. Existing views: %s.',
                implode(', ', $this->openApiRenderer->getAvailableViews())
            ));
        }

        return $view;
    }

    private function getFormat(InputInterface $input): string
    {
        $format = $input->getOption('format');
        if (!\in_array($format, $this->openApiRenderer->getAvailableFormats(), true)) {
            throw new InvalidArgumentException(sprintf(
                'The specified format is not supported. Supported formats: %s.',
                implode(', ', $this->openApiRenderer->getAvailableFormats())
            ));
        }

        return $format;
    }

    private function getOptions(InputInterface $input): array
    {
        $options = [];
        if ($input->hasParameterOption(['--no-validation'])) {
            $options['no-validation'] = true;
        }
        $entities = $input->getOption('entity');
        if ($entities) {
            $options['entities'] = $entities;
        }
        $title = $input->getOption('title');
        if ($title) {
            $options['title'] = $title;
        }
        $serverUrl = $input->getOption('server-url');
        if ($serverUrl) {
            $options['server_url'] = $serverUrl;
        }

        return $options;
    }

    public function getAvailableViews(): array
    {
        $result = [];
        $views = $this->openApiRenderer->getAvailableViews();
        foreach ($views as $view) {
            $result[] = sprintf(
                '%s (%s)',
                $view,
                $this->openApiChoicesProvider->getOpenApiSpecificationName($view)
            );
        }

        return $result;
    }
}
