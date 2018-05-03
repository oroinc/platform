<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Formatter;

use Oro\Component\MessageQueue\Client\Config;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter as BaseConsoleFormatter;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Formats message queue consumer related log records for the console output
 * by coloring them depending on log level.
 * The "%data%" placeholder is filled based on the specified $dataMap.
 */
class ConsoleFormatter extends BaseConsoleFormatter
{
    const SIMPLE_FORMAT = "%start_tag%%level_name%:%end_tag% %message% %data% %context%\n";

    /**
     * The variable map for "%data%" placeholder
     *
     * @var array [variable name => the path to the variable value, ...]
     */
    protected $dataMap;

    /**
     * @var CliDumper
     */
    protected $dumper;

    /**
     * @var bool|resource
     */
    protected $outputBuffer;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var VarCloner
     */
    protected $cloner;

    /**
     * Available options:
     *   * format: The format of the outputted log string.
     *   * date_format: The format of the outputted date string;
     *   * colors: If true, the log string contains ANSI code to add color;
     *   * multiline: If false, "context" and "extra" are dumped on one line.
     *   * data_map: The variable map for "%data%" placeholder
     *               [variable name => the path to the variable value, ...]
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_replace([
            'format' => self::SIMPLE_FORMAT,
            'multiline' => false,
            'colors' => false
        ], $options);

        $this->options['format'] = $this->prepareFormat($this->options['format']);

        parent::__construct($this->options);

        $this->dataMap = $this->options['data_map'] ?? null;
        if (null === $this->dataMap) {
            $this->dataMap = [
                'processor' => ['extra', 'processor'],
                'extension' => ['extra', 'extension'],
                'topic'     => ['extra', 'message_properties', Config::PARAMETER_TOPIC_NAME],
                'message'   => ['extra', 'message_body'],
            ];
        }

        $this->outputBuffer = fopen('php://memory', 'r+b');
        $this->cloner = new VarCloner();

        $this->dumper = new CliDumper(
            [$this, 'echoLineToBuffer'],
            null,
            CliDumper::DUMP_LIGHT_ARRAY | CliDumper::DUMP_COMMA_SEPARATOR
        );
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $data = [];
        foreach ($this->dataMap as $key => $path) {
            $value = $this->getDataValue($record, $path);
            if (null !== $value) {
                $data[$key] = $value;
            }
        }

        $formatted = parent::format($record);

        return strtr($formatted, [
            '%data%' => $data ? $this->dumpData($data) : '',
            '%custom_context%' => $record['context'] ? $this->dumpData($record['context']) : ''
        ]);
    }

    /**
     * @param mixed $data
     * @return string
     */
    protected function dumpData($data)
    {
        $this->dumper->setColors($this->options['colors']);

        $data = $this->cloner->cloneVar($data);
        $data = $data->withRefHandles(false);
        $this->dumper->dump($data);

        $dump = stream_get_contents($this->outputBuffer, -1, 0);
        rewind($this->outputBuffer);
        ftruncate($this->outputBuffer, 0);

        return rtrim($dump);
    }

    /**
     * Gets a value of the specified variable.
     *
     * @param array    $record The log record
     * @param string[] $path   The path to the variable
     *
     * @return mixed A value or NULL if the variable does not exist
     */
    protected function getDataValue(array $record, array $path)
    {
        $lastKey = array_pop($path);
        $current = $record;
        foreach ($path as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }
        if (!isset($current[$lastKey])) {
            return null;
        }

        return $current[$lastKey];
    }

    /**
     * @internal
     * @param string $line
     * @param int $depth
     * @param int $indentPad
     */
    public function echoLineToBuffer($line, $depth, $indentPad)
    {
        if (-1 !== $depth) {
            fwrite($this->outputBuffer, $line);
        }
    }

    /**
     * @param string $format
     * @return string
     */
    private function prepareFormat(string $format): string
    {
        return strtr($format, ['%context%' => '%custom_context%']);
    }

}
