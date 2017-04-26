<?php

namespace Oro\Bundle\DataGridBundle\Handler;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\BatchBundle\Step\StepExecutionWarningHandlerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;

class ExportHandler implements StepExecutionWarningHandlerInterface
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var bool
     */
    protected $exportFailed = false;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param ItemReaderInterface $reader
     * @param ExportProcessor $processor
     * @param ItemWriterInterface $writer
     * @param array $contextParameters
     * @param int $batchSize
     * @param string $format
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function handle(
        ItemReaderInterface $reader,
        ExportProcessor $processor,
        ItemWriterInterface $writer,
        array $contextParameters,
        $batchSize,
        $format
    ) {
        if (!isset($contextParameters['gridName'])) {
            throw new InvalidArgumentException('Parameter "gridName" must be provided.');
        }

        $fileName = FileManager::generateFileName(sprintf('datagrid_%s', $contextParameters['gridName']), $format);
        $filePath = FileManager::generateTmpFilePath($fileName);

        $contextParameters['filePath'] = $filePath;

        $context  = new Context($contextParameters);
        $executor = new StepExecutor();
        $executor->setBatchSize($batchSize);
        $executor
            ->setReader($reader)
            ->setProcessor($processor)
            ->setWriter($writer);
        foreach ([$executor->getReader(), $executor->getProcessor(), $executor->getWriter()] as $element) {
            if ($element instanceof ContextAwareInterface) {
                $element->setImportExportContext($context);
            }
        }

        $executor->execute($this);

        $url = null;
        $this->fileManager->writeFileToStorage($filePath, $fileName);
        unlink($filePath);

        if (! $this->exportFailed) {
            $url = $this->configManager->get('oro_ui.application_url') . $this->router->generate(
                'oro_importexport_export_download',
                ['fileName' => $fileName]
            );
        }

        return [
            'success' => !$this->exportFailed,
            'url' => $url,
         ];
    }

    /**
     * @param object $element
     * @param string $name
     * @param string $reason
     * @param array $reasonParameters
     * @param mixed $item
     */
    public function handleWarning($element, $name, $reason, array $reasonParameters, $item)
    {
        $this->exportFailed = true;

        $this->logger->error(sprintf('[DataGridExportHandle] Error message: %s', $reason), ['element' => $element]);
    }
}
