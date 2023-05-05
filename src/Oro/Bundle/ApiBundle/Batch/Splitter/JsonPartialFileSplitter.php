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

    /**
     * {@inheritDoc}
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimeout(int $milliseconds): void
    {
        $this->timeout = $milliseconds;
    }

    /**
     * {@inheritDoc}
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * {@inheritDoc}
     */
    public function setState(array $data): void
    {
        $this->state = $data;
        $this->headerSectionData = $this->state['headerSection'] ?? null;
        $this->targetFileIndex = $this->state['targetFileIndex'] ?? 0;
        $this->targetFileFirstRecordOffset = $this->state['targetFileFirstRecordOffset'] ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function splitFile(string $fileName, FileManager $srcFileManager, FileManager $destFileManager): array
    {
        try {
            return parent::splitFile($fileName, $srcFileManager, $destFileManager);
        } finally {
            $this->headerSectionData = $this->state['headerSection'] ?? null;
            $this->targetFileIndex = $this->state['targetFileIndex'] ?? 0;
            $this->targetFileFirstRecordOffset = $this->state['targetFileFirstRecordOffset'] ?? 0;
        }
    }

    /**
     * {@inheritDoc}
     */
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
            $this->chunkStartTime = null;
            $this->chunkTime = 0;
            $this->chunkCount = 0;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getParser($stream): Parser
    {
        return new JsonPartialFileParser($stream, $this->getParserListener());
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    protected function processItem(mixed $item): void
    {
        if (-1 !== $this->timeout && null === $this->chunkStartTime) {
            $this->chunkStartTime = microtime(true);
        }

        parent::processItem($item);
    }

    /**
     * {@inheritDoc}
     */
    protected function saveChunk(): void
    {
        parent::saveChunk();

        if (-1 !== $this->timeout) {
            $this->chunkCount++;
            $this->chunkTime += (int)round(1000 * (microtime(true) - $this->chunkStartTime));
            $this->chunkStartTime = null;

            if ($this->chunkTime >= $this->timeout
                || ((int)round($this->chunkTime / $this->chunkCount)) > $this->timeout - $this->chunkTime
            ) {
                throw new TimeoutExceededFileSplitterException();
            }
        }
    }
}
