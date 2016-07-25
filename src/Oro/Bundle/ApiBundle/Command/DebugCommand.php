<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\Debug\TraceableProcessor;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class DebugCommand extends AbstractDebugCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:debug')
            ->setDescription('Shows details about registered Data API actions and processors.')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'Shows a list of processors for a specified action'
            )
            ->addOption(
                'attribute',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Shows processors which will be executed only when the context has'
                . ' a given attribute with the specified value.'
                . ' The name and value should be separated by the colon,'
                . ' e.g.: <info>--attribute=collection:true</info> for scalar value'
                . ' or <info>--attribute=extra:[definition,filters]</info> for array value'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultRequestType()
    {
        return ['any'];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');
        if (empty($action)) {
            $this->dumpActions($output);
        } else {
            $this->dumpProcessors($output, $action, $this->getRequestType($input), $input->getOption('attribute'));
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function dumpActions(OutputInterface $output)
    {
        /** @var ActionProcessorBagInterface $processorBag */
        $actionProcessorBag = $this->getContainer()->get('oro_api.action_processor_bag');
        /** @var ProcessorBagInterface $processorBag */
        $processorBag = $this->getContainer()->get('oro_api.processor_bag');

        $output->writeln('<info>All Actions:</info>');
        $table = new Table($output);
        $table->setHeaders(['Action', 'Groups']);

        $i = 0;
        foreach ($processorBag->getActions() as $action) {
            if ($i > 0) {
                $table->addRow(new TableSeparator());
            }
            $table->addRow([$action, implode(PHP_EOL, $processorBag->getActionGroups($action))]);
            $i++;
        }

        $table->render();

        $output->writeln('<info>Public Actions:</info>');
        foreach ($actionProcessorBag->getActions() as $action) {
            $output->writeln('  ' . $action);
        }

        $output->writeln('');
        $output->writeln(
            sprintf(
                'To run show a list of processors for some action run <info>%s ACTION</info>',
                $this->getName()
            )
        );
    }

    /**
     * @param OutputInterface $output
     * @param string          $action
     * @param RequestType     $requestType
     * @param string[]        $attributes
     */
    protected function dumpProcessors(OutputInterface $output, $action, RequestType $requestType, array $attributes)
    {
        $output->writeln('The processors are displayed in the order they are executed.');

        /** @var ProcessorBagInterface $processorBag */
        $processorBag = $this->getContainer()->get('oro_api.processor_bag');

        $table = new Table($output);
        $table->setHeaders(['Processor', 'Attributes']);

        $context = new Context();
        $context->setAction($action);
        $context->set(ApiContext::REQUEST_TYPE, $requestType);
        $specifiedAttributes = [];
        foreach ($attributes as $attribute) {
            list($name, $value) = explode(':', $attribute, 2);
            $context->set($name, $this->getTypedValue($value));
            $specifiedAttributes[] = $name;
        }
        $processors = $processorBag->getProcessors($context);

        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new Util\RequestTypeApplicableChecker());
        $applicableChecker->addChecker(new Util\AttributesApplicableChecker($specifiedAttributes));
        $processors->setApplicableChecker($applicableChecker);

        $i = 0;
        foreach ($processors as $processor) {
            if ($i > 0) {
                $table->addRow(new TableSeparator());
            }

            if ($processor instanceof TraceableProcessor) {
                $processor = $processor->getProcessor();
            }

            $processorColumn      = sprintf(
                '<comment>%s</comment>%s%s',
                $processors->getProcessorId(),
                PHP_EOL,
                get_class($processor)
            );
            $processorDescription = $this->getClassDocComment(get_class($processor));
            if (!empty($processorDescription)) {
                $processorColumn .= PHP_EOL . $processorDescription;
            }

            $attributesColumn = $this->formatProcessorAttributes($processors->getProcessorAttributes());

            $table->addRow(
                [
                    $processorColumn,
                    $attributesColumn
                ]
            );
            $i++;
        }

        $table->render();
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function getClassDocComment($className)
    {
        $reflection = new \ReflectionClass($className);

        $comment = $reflection->getDocComment();

        $comment = preg_replace('/^\s+\* @[\w0-9]+.*/msi', '', $comment);
        $comment = strtr($comment, ['/**' => '', '*/' => '']);
        $comment = preg_replace('/^\s+\* ?/m', '', $comment);

        return trim($comment);
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    protected function formatProcessorAttributes(array $attributes)
    {
        $rows = [];

        if (array_key_exists('group', $attributes)) {
            $rows[] = implode(
                ': ',
                [
                    'group',
                    sprintf(
                        '<comment>%s</comment>',
                        $this->convertProcessorAttributeValueToString($attributes['group'])
                    )
                ]
            );
            unset($attributes['group']);
        }

        foreach ($attributes as $key => $val) {
            $rows[] = implode(': ', [$key, $this->convertProcessorAttributeValueToString($val)]);
        }

        return implode(PHP_EOL, $rows);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function convertProcessorAttributeValueToString($value)
    {
        if (!is_array($value)) {
            return $this->convertValueToString($value);
        }

        $items = reset($value);
        if (!is_array($items)) {
            return sprintf('<comment>%s</comment>%s', key($value), $items);
        }

        return implode(
            sprintf(' <comment>%s</comment> ', key($value)),
            array_map(
                function ($val) {
                    if (is_array($val)) {
                        $item = reset($val);

                        return sprintf('<comment>%s</comment>%s', key($val), $item);
                    }

                    return $val;
                },
                $items
            )
        );
    }
}
