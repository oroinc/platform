<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

use JsonStreamingParser\Listener\PositionAwareInterface;
use JsonStreamingParser\Parser;

/**
 * JSON streaming parser that allows to parse only a part of file.
 * Unfortunately it is not possible to implement this class without the reflection
 * because the base parser protects its state with "private" keyword.
 */
class JsonPartialFileParser extends Parser
{
    private int $offset = 0;
    private \Closure $privatePropertyAccessor;
    private \Closure $consumeCharAccessor;

    /**
     * @param resource                        $stream
     * @param JsonPartialFileSplitterListener $listener
     */
    public function __construct($stream, JsonPartialFileSplitterListener $listener)
    {
        parent::__construct($stream, $listener);

        $this->privatePropertyAccessor = \Closure::bind(
            function & ($name) {
                if (!property_exists($this, $name)) {
                    throw new \ReflectionException(sprintf('The property "%s" does not exist.', $name));
                }

                return $this->{$name};
            },
            $this,
            Parser::class
        );
        $this->consumeCharAccessor = \Closure::bind(
            function ($parser, $c) {
                $parser->consumeChar($c);
            },
            null,
            Parser::class
        );
    }

    /**
     * Gets the state of the parser.
     */
    public function getState(): array
    {
        return [
            'offset'     => $this->offset,
            'lineNumber' => $this->getPrivateProperty('lineNumber'),
            'charNumber' => $this->getPrivateProperty('charNumber'),
            'state'      => $this->getPrivateProperty('state'),
            'stack'      => $this->getPrivateProperty('stack'),
            'listener'   => $this->getListener()->getState()
        ];
    }

    /**
     * Restores the state of the parser.
     */
    public function setState(array $data): void
    {
        if (\array_key_exists('offset', $data)) {
            $this->offset = $data['offset'];
        }
        if (\array_key_exists('lineNumber', $data)) {
            $this->setPrivateProperty('lineNumber', $data['lineNumber']);
        }
        if (\array_key_exists('charNumber', $data)) {
            $this->setPrivateProperty('charNumber', $data['charNumber']);
        }
        if (\array_key_exists('state', $data)) {
            $this->setPrivateProperty('state', $data['state']);
        }
        if (\array_key_exists('stack', $data)) {
            $this->setPrivateProperty('stack', $data['stack']);
        }
        if (\array_key_exists('listener', $data)) {
            $this->getListener()->setState($data['listener']);
        }
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function parse(): void
    {
        $privatePropertyAccessor = $this->privatePropertyAccessor;
        $lineNumber = &$privatePropertyAccessor('lineNumber');
        $charNumber = &$privatePropertyAccessor('charNumber');
        $stopParsing = &$privatePropertyAccessor('stopParsing');
        $stream = $this->getPrivateProperty('stream');
        $bufferSize = $this->getPrivateProperty('bufferSize');
        $lineEnding = $this->getPrivateProperty('lineEnding');
        $listener = $this->getPrivateProperty('listener');

        if (null === $lineNumber) {
            $lineNumber = 1;
        }
        if (null === $charNumber) {
            $charNumber = 1;
        }

        if ($this->offset > 0) {
            fseek($stream, $this->offset);
        }
        $eof = false;
        while (!feof($stream) && !$eof) {
            $startPos = ftell($stream);
            $line = stream_get_line($stream, $bufferSize, $lineEnding);
            $endPos = ftell($stream);
            $ended = (bool)($endPos - \strlen($line) - $startPos);
            // if we're still at the same place after stream_get_line, we're done
            $eof = $endPos === $startPos;

            $byteLen = \strlen($line);
            for ($i = 0; $i < $byteLen; $i++) {
                if ($listener instanceof PositionAwareInterface) {
                    $listener->setFilePosition($lineNumber, $charNumber);
                }
                $this->offset++;
                \call_user_func($this->consumeCharAccessor, $this, $line[$i]);
                $charNumber++;

                if ($stopParsing) {
                    return;
                }
            }

            if ($ended) {
                $lineNumber++;
                $charNumber = 1;
                $this->offset++;
            }
        }
    }

    private function getListener(): JsonPartialFileSplitterListener
    {
        return $this->getPrivateProperty('listener');
    }

    private function getPrivateProperty(string $name): mixed
    {
        $privatePropertyAccessor = $this->privatePropertyAccessor;

        return $privatePropertyAccessor($name);
    }

    private function setPrivateProperty(string $name, mixed $value): void
    {
        $privatePropertyAccessor = $this->privatePropertyAccessor;
        $property = &$privatePropertyAccessor($name);
        $property = $value;
    }
}
