<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

use Gaufrette\Stream;
use Gaufrette\StreamMode;
use JsonStreamingParser\Exception\ParsingException;
use JsonStreamingParser\Listener\ListenerInterface;
use JsonStreamingParser\Parser;
use Oro\Bundle\ApiBundle\Batch\JsonUtil;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Exception\FileSplitterException;
use Oro\Bundle\ApiBundle\Exception\ParsingErrorFileSplitterException;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\GaufretteBundle\Stream\ReadonlyResourceStream;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Splits a JSON file to chunks.
 */
class JsonFileSplitter implements FileSplitterInterface
{
    /** @var string|null The name of the current first level section */
    protected ?string $sectionName = null;
    /** @var array|null The header section data */
    protected ?array $headerSectionData = null;
    /** @var int Internal counter of files that were saved during split operation */
    protected int $targetFileIndex = 0;
    /** @var int Internal counter of records in files that were saved during split operation */
    protected int $targetFileFirstRecordOffset = 0;
    private ?string $headerSectionName = null;
    /** @var string[] */
    private array $sectionNamesToSplit = [];
    private int $chunkSize = 100;
    /** @var array [section name => chunk size, ...] */
    private array $chunkSizePerSection = [];
    private ?string $chunkFileNameTemplate = null;
    private ?FileManager $destFileManager = null;
    /** @var array Internal buffer of parsed objects */
    private array $buffer = [];
    /** @var ChunkFile[] Chunk files that were saved during split operation */
    private array $targetFiles = [];

    /**
     * {@inheritDoc}
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * {@inheritDoc}
     */
    public function setChunkSize(int $size): void
    {
        $this->chunkSize = $size;
    }

    /**
     * {@inheritDoc}
     */
    public function getChunkSizePerSection(): array
    {
        return $this->chunkSizePerSection;
    }

    /**
     * {@inheritDoc}
     */
    public function setChunkSizePerSection(array $sizes): void
    {
        $this->chunkSizePerSection = $sizes;
    }

    /**
     * {@inheritDoc}
     */
    public function getChunkFileNameTemplate(): ?string
    {
        return $this->chunkFileNameTemplate;
    }

    /**
     * {@inheritDoc}
     */
    public function setChunkFileNameTemplate(?string $template): void
    {
        $this->chunkFileNameTemplate = $template;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderSectionName(): ?string
    {
        return $this->headerSectionName;
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaderSectionName(?string $name): void
    {
        $this->headerSectionName = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getSectionNamesToSplit(): array
    {
        return $this->sectionNamesToSplit;
    }

    /**
     * {@inheritDoc}
     */
    public function setSectionNamesToSplit(array $names): void
    {
        $this->sectionNamesToSplit = $names;
    }

    /**
     * {@inheritDoc}
     */
    public function splitFile(string $fileName, FileManager $srcFileManager, FileManager $destFileManager): array
    {
        $this->destFileManager = $destFileManager;
        $stream = null;
        try {
            $stream = $this->openFileStreamToRead($srcFileManager, $fileName);
            $this->parseStream($stream);

            return $this->targetFiles;
        } catch (ParsingException $e) {
            throw new ParsingErrorFileSplitterException(
                $fileName,
                $this->getChunkFileNames($this->targetFiles),
                $e
            );
        } catch (\Throwable $e) {
            throw new FileSplitterException(
                $fileName,
                $this->getChunkFileNames($this->targetFiles),
                $e
            );
        } finally {
            $this->destFileManager = null;
            $this->sectionName = null;
            $this->targetFiles = [];
            $this->targetFileIndex = 0;
            $this->targetFileFirstRecordOffset = 0;
            $stream?->close();
        }
    }

    protected function parse(Parser $parser): void
    {
        $parser->parse();

        // make sure that the last chunk is saved
        if (!empty($this->buffer)) {
            $this->saveChunk();
        }
    }

    /**
     * @param resource $stream
     *
     * @return Parser
     */
    protected function getParser($stream): Parser
    {
        return new Parser($stream, $this->getParserListener());
    }

    protected function getParserListener(): ListenerInterface
    {
        return new JsonFileSplitterListener(
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

    protected function processSection(string $item): void
    {
        if (!$this->sectionName && $item && $this->headerSectionName === $item) {
            return;
        }
        if ($this->sectionName && !empty($this->buffer)) {
            $this->saveChunk();
        }
        if ($item && $item !== $this->sectionName) {
            $this->targetFileFirstRecordOffset = 0;
        }
        $this->sectionName = $item;
    }

    protected function processItem(mixed $item): void
    {
        if ($this->sectionName
            && !empty($this->sectionNamesToSplit)
            && !\in_array($this->sectionName, $this->sectionNamesToSplit, true)
        ) {
            return;
        }

        $this->buffer[] = $item;
        if (!empty($this->buffer) && (\count($this->buffer) % $this->getChunkSizeForSection() === 0)) {
            $this->saveChunk();
        }
    }

    protected function processHeader(array $header): void
    {
        $this->headerSectionData = $header;
    }

    protected function getChunkSizeForSection(): int
    {
        if (!$this->sectionName) {
            return $this->chunkSize;
        }

        return $this->chunkSizePerSection[$this->sectionName] ?? $this->chunkSize;
    }

    /**
     * Saves the buffer to a new chunk file
     */
    protected function saveChunk(): void
    {
        $data = $this->buffer;
        if ($this->sectionName) {
            $item = [$this->sectionName => $data];
            if (null !== $this->headerSectionData) {
                $item = array_merge($this->headerSectionData, $item);
            }
            $data = $item;
        }
        $fileName = $this->saveChunkFile($data);

        $this->targetFiles[] = new ChunkFile(
            $fileName,
            $this->targetFileIndex,
            $this->targetFileFirstRecordOffset,
            $this->sectionName ?: null
        );
        $this->buffer = [];
        $this->targetFileIndex++;
        $this->targetFileFirstRecordOffset += $this->getChunkSizeForSection();
    }

    /**
     * @param array $data
     *
     * @return string The name of the created file
     */
    protected function saveChunkFile(array $data): string
    {
        $fileName = UUIDGenerator::v4();
        if ($this->chunkFileNameTemplate) {
            $fileName = sprintf($this->chunkFileNameTemplate, $fileName);
        }

        $this->destFileManager->writeToStorage(JsonUtil::encode($data), $fileName);

        return $fileName;
    }

    private function openFileStreamToRead(FileManager $fileManager, string $fileName): Stream
    {
        $stream = $fileManager->getStream($fileName);
        if (null === $stream) {
            throw new \RuntimeException('Cannot get the stream.');
        }
        if (!$stream->open(new StreamMode('r'))) {
            throw new \RuntimeException('Cannot open the stream.');
        }
        if ($stream instanceof Stream\InMemoryBuffer) {
            $resource = fopen('php://memory', 'rb+');
            fwrite($resource, $stream->read(PHP_INT_MAX));
            rewind($resource);
            $stream = new ReadonlyResourceStream($resource);
        }

        return $stream;
    }

    private function parseStream(Stream $stream): void
    {
        $resource = $stream->cast('stream');
        if (false === $resource) {
            throw new \RuntimeException('Cannot get the stream resource.');
        }

        $this->parse($this->getParser($resource));
    }

    /**
     * @param ChunkFile[] $chunkFiles
     *
     * @return string[]
     */
    private function getChunkFileNames(array $chunkFiles): array
    {
        return array_map(
            function (ChunkFile $file) {
                return $file->getFileName();
            },
            $chunkFiles
        );
    }
}
