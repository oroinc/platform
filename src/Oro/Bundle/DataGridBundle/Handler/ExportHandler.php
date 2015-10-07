<?php

namespace Oro\Bundle\DataGridBundle\Handler;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\MimeType\MimeTypeGuesser;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Context\Context;

class ExportHandler
{
    /**
     * @var MimeTypeGuesser
     */
    protected $guesser;

    /**
     * @param MimeTypeGuesser $guesser
     */
    public function __construct(MimeTypeGuesser $guesser)
    {
        $this->guesser = $guesser;
    }

    /**
     * @param ItemReaderInterface $reader
     * @param ExportProcessor     $processor
     * @param ItemWriterInterface $writer
     * @param array               $contextParameters
     * @param                     $batchSize
     * @param                     $format
     * @return StreamedResponse
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

        $contentType = $this->guesser->guessByFileExtension($format);
        if (!$contentType) {
            $contentType = 'application/octet-stream';
        }

        // prepare response
        $response = new StreamedResponse($this->exportCallback($context, $executor));
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $outputFileName = sprintf(
            'datagrid_%s_%s.%s',
            str_replace('-', '_', $contextParameters['gridName']),
            date('Y_m_d_H_i_s'),
            $format
        );
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $outputFileName)
        );

        return $response;
    }

    /**
     * @param ContextInterface $context
     * @param StepExecutor     $executor
     *
     * @return \Closure
     */
    protected function exportCallback(ContextInterface $context, StepExecutor $executor)
    {
        return function () use ($executor) {
            flush();
            $executor->execute();
        };
    }
}
