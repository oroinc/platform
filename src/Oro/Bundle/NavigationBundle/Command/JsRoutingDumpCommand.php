<?php
declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Command;

use FOS\JsRoutingBundle\Extractor\ExposedRoutesExtractorInterface;
use FOS\JsRoutingBundle\Response\RoutesResponse;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Dumps exposed routes into a file.
 * This class is a copy of {@see \FOS\JsRoutingBundle\Command\DumpCommand}, but with the following differences:
 * * changed default values for "format" and "target" options
 * * support of the Gaufrette path for the target file
 */
class JsRoutingDumpCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'fos:js-routing:dump';

    private ExposedRoutesExtractorInterface $extractor;
    private SerializerInterface $serializer;
    private ?string $requestContextBaseUrl;
    protected string $filenamePrefix;
    protected FileManager $fileManager;

    public function __construct(
        ExposedRoutesExtractorInterface $extractor,
        SerializerInterface $serializer,
        ?string $requestContextBaseUrl,
        string $filenamePrefix,
        FileManager $fileManager
    ) {
        $this->extractor = $extractor;
        $this->serializer = $serializer;
        $this->requestContextBaseUrl = $requestContextBaseUrl;
        $this->filenamePrefix = $filenamePrefix;
        $this->fileManager = $fileManager;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dumps exposed routes into a file.')
            ->addOption(
                'callback',
                null,
                InputOption::VALUE_REQUIRED,
                'Callback function to pass the routes as an argument.',
                'fos.Router.setData'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'Format to output routes in. js to wrap the response in a callback, json for raw json output.'
                . ' Callback is ignored when format is json',
                'json'
            )
            ->addOption(
                'target',
                null,
                InputOption::VALUE_OPTIONAL,
                'Override the target directory to dump routes in.',
                $this->fileManager->getFilePath($this->filenamePrefix . 'routes.json')
            )
            ->addOption(
                'locale',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set locale to be used with JMSI18nRoutingBundle.',
                ''
            )
            ->addOption(
                'pretty-print',
                'p',
                InputOption::VALUE_NONE,
                'Pretty print the JSON.'
            )
            ->addOption(
                'domain',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify expose domain',
                ''
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = $input->getOption('format');
        if (!\in_array($format, ['js', 'json'])) {
            $output->writeln('<error>Invalid format specified. Use js or json.</error>');

            return 1;
        }

        $callback = $input->getOption('callback');
        if (empty($callback)) {
            $output->writeln(
                '<error>If you include --callback it must not be empty. Do you perhaps want --format=json</error>'
            );

            return 1;
        }

        if ('json' !== $format) {
            $targetOption = $this->getDefinition()->getOption('target');
            $targetOption->setDefault(
                substr($targetOption->getDefault(), 0, strrpos($targetOption->getDefault(), '.') + 1) . $format
            );
        }

        $output->writeln('Dumping exposed routes.');
        $output->writeln('');

        $this->dump($input, $output);

        return 0;
    }

    protected function normalizeTargetPath(string $targetPath): string
    {
        return $targetPath;
    }

    private function dump(InputInterface $input, OutputInterface $output): void
    {
        $targetPath = $input->getOption('target');
        if (!$targetPath) {
            $targetPath = $this->getDefinition()->getOption('target')->getDefault();
        }
        $targetPath = $this->normalizeTargetPath($targetPath);

        $this->ensureTargetDirectoryExists($targetPath, $output);

        $output->writeln('<info>[file+]</info> ' . $targetPath);

        $content = $this->serializer->serialize(
            new RoutesResponse(
                $this->requestContextBaseUrl ?? $this->extractor->getBaseUrl(),
                $this->extractor->getRoutes(),
                $this->extractor->getPrefix($input->getOption('locale')),
                $this->extractor->getHost(),
                $this->extractor->getPort(),
                $this->extractor->getScheme(),
                $input->getOption('domain')
            ),
            'json',
            $input->getOption('pretty-print') ? ['json_encode_options' => JSON_PRETTY_PRINT] : []
        );

        if ('js' === $input->getOption('format')) {
            $content = sprintf('%s(%s);', $input->getOption('callback'), $content);
        }

        if (false === @file_put_contents($targetPath, $content)) {
            throw new \RuntimeException('Unable to write file ' . $targetPath);
        }
    }

    private function ensureTargetDirectoryExists(string $targetPath, OutputInterface $output): void
    {
        if (str_starts_with($targetPath, $this->fileManager->getProtocol() . '://')) {
            return;
        }

        $dir = \dirname($targetPath);
        if (!is_dir($dir)) {
            $output->writeln('<info>[dir+]</info>  ' . $dir);
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException('Unable to create directory ' . $dir);
            }
        }
    }
}
