<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli\Descriptor;

use Symfony\Component\Console\Descriptor\DescriptorInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Descriptor which generates a *.md format from the array and outputs the result to the terminal.
 */
class MarkdownDescriptor implements DescriptorInterface
{
    private const TYPE_WRAP = [
        'code' => '```'
    ];

    private ?OutputInterface $output;

    private $columnLength = [];

    /**
     * Generates a *.md format from the array
     *
     * $object = [
     *     ['col1Val1', 'col2Val1'],
     *     ['col1Val2', 'col2Val2'],
     *     ['col1Val3', ['type' => 'code', 'code' => 'php', value' => '$var = 1;']]
     * ];
     * $options = [
     *      'format' => 'list'|'table',
     *      'tableName' => 'Table Name',
     *      'headers' => ['Col 1 Name', 'Col 2 Name']
     * ];
     *
     * Output example [format => 'table']:
     *
     * | Col 1 Name | Col 2 Name      |
     * | ---------- | --------------- |
     * | col1Val1   | col2Val1        |
     * | col1Val2   | col2Val2        |
     * | col1Val3   | ```$var = 1;``` |
     *
     * Output example [format => 'table']:
     *
     * col1Val1
     * col2Val1
     * col1Val2
     * col2Val2
     * col1Val3
     * ```php
     * $var = 1;
     * ```
     *
     * @param array $object
    */
    public function describe(OutputInterface $output, $object, array $options = []): void
    {
        $this->output = $output;

        switch ($options['format']) {
            case 'table':
                $this->describeTable($object, $options);
                break;
            case 'list':
                $this->describeList($object);
                break;
            default:
                break;
        }
    }

    private function describeTable(array $list, array $options = []): void
    {
        $this->defineColumnMaxLength($list);
        $this->describeHeaders($options);

        foreach ($list as $rowElement) {
            $row = '| ';
            foreach ($rowElement as $key => $columnElement) {
                $value = $columnElement;
                if (is_array($columnElement)) {
                    $row .= (self::TYPE_WRAP[$columnElement['type']] ?? '') . $columnElement['value'];
                    $row .= self::TYPE_WRAP[$columnElement['type']] ?? '';
                    $value = $columnElement['value'];
                } else {
                    $row .= $columnElement;
                }
                $row .= str_repeat(' ', $this->columnLength[$key] - strlen($value)) . ' | ';
            }

            $this->output->writeln($row);
        }
    }

    private function defineColumnMaxLength(array $list): void
    {
        foreach ($list[0] as $key => $columnElement) {
            $this->columnLength[$key] = max(
                array_map(
                    'strlen',
                    array_map(
                        fn ($el) => is_array($el) ? $el['value'] : $el,
                        array_column($list, $key)
                    )
                )
            );
        }
    }

    private function describeHeaders(array $options = []): void
    {

        $headers = '| ';
        $columns = '| ';
        foreach ($options['headers'] ?? [] as $key => $header) {
            $repeatSpace = $this->columnLength[$key] < strlen($header)
                ? 0
                : $this->columnLength[$key] - strlen($header);
            $repeatHyphens = max($this->columnLength[$key], strlen($header));
            $headers .= $header . str_repeat(' ', $repeatSpace) . ' | ';
            $columns .= str_repeat('-', $repeatHyphens)  . ' | ';
        }
        $this->output->writeln($headers);
        $this->output->writeln($columns);
    }

    private function describeList(array $list): void
    {
        foreach ($list as $rowElement) {
            foreach ($rowElement as $columnElement) {
                if (is_array($columnElement)) {
                    $this->output->writeln(
                        (self::TYPE_WRAP[$columnElement['type']] ?? '') . ($columnElement['code'] ?? '')
                    );
                    $this->output->writeln($columnElement['value']);
                    $this->output->writeln(self::TYPE_WRAP[$columnElement['type']] ?? '');
                    continue;
                }
                $this->output->writeln($columnElement);
            }
        }
    }
}
