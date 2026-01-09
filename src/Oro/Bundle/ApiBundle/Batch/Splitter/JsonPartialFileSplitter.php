<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

use JsonStreamingParser\Parser;
use Oro\Bundle\ApiBundle\Exception\TimeoutExceededFileSplitterException;
use Oro\Bundle\GaufretteBundle\FileManager;

/**
 * Splits a JSON file to chunks with possibility to limit the splitting time.
 */
class JsonPartialFileSplitter extends JsonFileSplitter implements PartialFileSplitterInterface
{
    private bool $completed = false;
    private int $timeout = -1;
    private array $state = [];
    private ?float $chunkStartTime = null;
    private int $chunkTime = 0;
    private int $chunkCount = 0;

    #[\Override]
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    #[\Override]
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    #[\Override]
    public function setTimeout(int $milliseconds): void
    {
        $this->timeout = $milliseconds;
    }

    #[\Override]
    public function getState(): array
    {
        return $this->state;
    }

    #[\Override]
    public function setState(array $data): void
    {
        $this->state = $data;
        $this->updateFromState();
    }

    #[\Override]
    public function splitFile(string $fileName, FileManager $srcFileManager, FileManager $destFileManager): array
    {
        try {
            return parent::splitFile($fileName, $srcFileManager, $destFileManager);
        } finally {
            $this->updateFromState();
        }
    }

    #[\Override]
    protected function parse(Parser $parser): void
    {
        /** @var JsonPartialFileParser $parser */

        $this->completed = true;
        $parser->setState($this->state);
        if (\array_key_exists('sectionName', $this->state)) {
            $this->sectionName = $this->state['sectionName'];
        }
        try {
            parent::parse($parser);
        } catch (TimeoutExceededFileSplitterException $e) {
            $this->completed = false;
        } finally {
            $this->state = $parser->getState();
            $this->state['sectionName'] = $this->sectionName;
            if (null !== $this->headerSectionData) {
                $this->state['headerSection'] = $this->headerSectionData;
            }
            $this->state['targetFileIndex'] = $this->targetFileIndex;
            $this->state['targetFileFirstRecordOffset'] = $this->targetFileFirstRecordOffset;
            $this->state['processedChunkCounts'] = $this->getProcessedChunkCounts();
            $this->chunkStartTime = null;
            $this->chunkTime = 0;
            $this->chunkCount = 0;
        }
    }

    #[\Override]
    protected function getParser($stream): Parser
    {
        return new JsonPartialFileParser($stream, $this->getParserListener());
    }

    #[\Override]
    protected function getParserListener(): JsonPartialFileSplitterListener
    {
        return new JsonPartialFileSplitterListener(
            function ($item) {
                $this->processSection($item);
            },
            function ($item) {
                $this->processItem($item);
            },
            $this->getHeaderSectionName(),
            function (array $header) {
                $this->processHeader($header);
            },
            $this->getSectionNamesToSplit()
        );
    }

    #[\Override]
    protected function processItem(mixed $item): void
    {
        if (-1 !== $this->timeout && null === $this->chunkStartTime) {
            $this->chunkStartTime = microtime(true);
        }

        parent::processItem($item);
    }

    #[\Override]
    protected function saveChunk(): void
    {
        parent::saveChunk();

        if (-1 !== $this->timeout) {
            $this->chunkCount++;
            $this->chunkTime += (int)round(1000 * (microtime(true) - $this->chunkStartTime));
            $this->chunkStartTime = null;

            if (
                $this->chunkTime >= $this->timeout
                || ((int)round($this->chunkTime / $this->chunkCount)) > $this->timeout - $this->chunkTime
            ) {
                throw new TimeoutExceededFileSplitterException();
            }
        }
    }

    protected function updateFromState(): void
    {
        $this->headerSectionData = $this->state['headerSection'] ?? null;
        $this->targetFileIndex = $this->state['targetFileIndex'] ?? 0;
        $this->targetFileFirstRecordOffset = $this->state['targetFileFirstRecordOffset'] ?? 0;
        $this->setProcessedChunkCounts($this->state['processedChunkCounts'] ?? []);
    }
}
