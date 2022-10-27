<?php

namespace Oro\Bundle\LayoutBundle\Console\Descriptor;

use Oro\Bundle\LayoutBundle\Command\Util\DebugOptionsResolverDecorator;
use Oro\Component\Layout\BlockTypeInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Text format descriptor for {@see \Oro\Bundle\LayoutBundle\Command\DebugCommand}
 */
class TextDescriptor extends AbstractDescriptor
{
    /**
     * {@inheritDoc}
     */
    protected function describeDefaults(array $options): void
    {
        $this->output->section('Context');
        $context = [];
        foreach ($options['context'] as $name => $value) {
            $context[] = [$name.':', $this->formatDefaultOptionValue($value)];
        }

        $table = new Table($this->output);
        $table->setRows($context);
        $table->setStyle('compact');
        $table->render();

        $this->output->section('Context Configurators');
        $this->output->listing($options['context_configurators']);

        $this->output->section('Block Types');
        $this->output->listing($options['block_types']);

        $this->output->section('Data Providers');
        $this->output->listing($options['data_providers']);
    }

    /**
     * {@inheritDoc}
     */
    protected function describeBlockType(BlockTypeInterface $blockType, array $blockOptions = []): void
    {
        $this->output->writeln(sprintf('<options=underscore>Class:</> %s', $blockOptions['class']));
        $this->output->writeln(
            sprintf('<options=underscore>Type inheritance:</> %s', implode(' <- ', $blockOptions['hierarchy']))
        );

        /** @var DebugOptionsResolverDecorator $resolver */
        $resolver = $blockOptions['options_resolver'];
        $this->output->section('Options');
        $table = new Table($this->output);
        $table->setHeaders(['Name', 'Default Value']);
        $table->setRows([]);
        $rows = $resolver->getOptions();
        foreach ($rows as &$row) {
            if ($row['required']) {
                $row['name'] .= ' <comment>(*required)</comment>';
            }
            unset($row['required']);
            $row['defaultValue'] = $this->formatDefaultOptionValue($row['defaultValue']);
        }
        unset($row);
        $table->setRows($rows);
        $table->render();
        if (!empty($blockOptions['type_extensions'])) {
            $this->output->section('Type extensions');
            $this->output->listing($blockOptions['type_extensions']);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function describeDataProvider($dataProvider, array $options = []): void
    {
        $this->output->text('<options=underscore>Name:</> '.$options['name']);
        $this->output->text(sprintf('<options=underscore>Class:</> %s', $options['class']));
        $this->output->newLine();
        foreach ($options['methods'] as $method) {
            $this->output->section(
                '\''.
                '=data["'.$options['name'].'"].<fg=green>'.$method['name'].'</>('.implode(
                    ', ',
                    array_keys($method['arguments'])
                ).')\''
            );
            if (!empty($method['description'])) {
                $this->output->block($method['description']);
            }
            if ($method['arguments']) {
                $this->output->writeln('  <options=underscore>arguments:</>');
                foreach ($method['arguments'] as $argument) {
                    $this->output->write('   * ');
                    $this->output->write('<options=bold>'.$argument['name'].'</>');
                    if (array_key_exists('default', $argument)) {
                        $this->output->write(' = '.$this->formatDefaultOptionValue($argument['default']));
                    }
                    if ($argument['type']) {
                        $this->output->write(' ['.$argument['type'].'] ');
                    }
                    if (!$argument['required']) {
                        $this->output->write('<comment>(optional)</comment> ');
                    }
                    if (!empty($argument['description'])) {
                        $this->output->block(
                            '    '.$argument['description'],
                            null,
                            'fg=default;bg=default',
                            '    '
                        );
                    }
                    $this->output->newLine();
                }
            }
        }
    }

    /**
     * @param $value
     * @return false|string
     */
    protected function formatDefaultOptionValue($value)
    {
        if ($value === DebugOptionsResolverDecorator::NO_VALUE) {
            return '';
        }

        return is_string($value) ? '"'.$value.'"' : json_encode($value);
    }
}
