<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\Debug\TraceableProcessor;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The CLI command to show different kind of debug information about API.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DebugCommand extends AbstractDebugCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var string */
    protected static $defaultName = 'oro:api:debug';

    /** @var ActionProcessorBagInterface */
    private $actionProcessorBag;

    /** @var ProcessorBagInterface */
    private $processorBag;

    /**
     * @param ValueNormalizer $valueNormalizer
     * @param ResourcesProvider $resourcesProvider
     * @param ActionProcessorBagInterface $actionProcessorBag
     * @param ProcessorBagInterface $processorBag
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        ResourcesProvider $resourcesProvider,
        ActionProcessorBagInterface $actionProcessorBag,
        ProcessorBagInterface $processorBag
    ) {
        parent::__construct($valueNormalizer, $resourcesProvider);

        $this->actionProcessorBag = $actionProcessorBag;
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Shows details about registered API actions and processors.')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'Shows a list of processors for a specified action'
            )
            ->addArgument(
                'group',
                InputArgument::OPTIONAL,
                'Shows a list of processors for a specified action and from a specified group'
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
            )
            ->addOption(
                'processors',
                null,
                InputOption::VALUE_NONE,
                'Shows a list of all processors'
            )
            ->addOption(
                'processors-without-description',
                null,
                InputOption::VALUE_NONE,
                'Shows a list of all processors without a description'
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
        $showProcessors = $input->getOption('processors');
        if ($showProcessors) {
            $this->dumpAllProcessors($output, $this->getRequestType($input));

            return;
        }

        $showProcessorsWithoutDescription = $input->getOption('processors-without-description');
        if ($showProcessorsWithoutDescription) {
            $this->dumpProcessorsWithoutDescription($output, $this->getRequestType($input));

            return;
        }

        $action = $input->getArgument('action');
        if (empty($action)) {
            $this->dumpActions($output);

            return;
        }

        /** @var string[] $attributes */
        $attributes = $input->getOption('attribute');
        $group = $input->getArgument('group');
        if ($group) {
            $attributes[] = sprintf('group:%s', $group);
        }
        $this->dumpProcessors($output, $action, $this->getRequestType($input), $attributes);
    }

    /**
     * @param OutputInterface $output
     */
    protected function dumpActions(OutputInterface $output)
    {
        $publicActions = $this->actionProcessorBag->getActions();

        $processorsForPublicActions = [];
        $processorsForOtherActions = [];
        $totalNumberOfProcessors = 0;
        $allProcessorsIds = [];
        foreach ($this->processorBag->getActions() as $action) {
            $processorIds = $this->getProcessorIds($action);
            $allProcessorsIds = array_merge($allProcessorsIds, $processorIds);
            $numberOfProcessors = count($processorIds);
            $totalNumberOfProcessors += $numberOfProcessors;
            $processorInfo = [$numberOfProcessors, $this->processorBag->getActionGroups($action)];
            if (in_array($action, $publicActions, true)) {
                $processorsForPublicActions[$action] = $processorInfo;
            } else {
                $processorsForOtherActions[$action] = $processorInfo;
            }
        }
        $allProcessorsIds = array_unique($allProcessorsIds);
        $processors = [];
        foreach ($processorsForOtherActions as $action => $processorInfo) {
            $processors[$action] = $processorInfo;
        }
        foreach ($publicActions as $action) {
            if (isset($processorsForPublicActions[$action])) {
                $processors[$action] = $processorsForPublicActions[$action];
            }
        }

        $output->writeln('<info>All Actions:</info>');
        $this->dumpAllActions($output, $processors);

        $output->writeln(sprintf(
            '<info>Total number of processors in the ProcessorBag:</info> %s',
            $totalNumberOfProcessors
        ));
        $output->writeln(sprintf(
            '<info>Total number of processor instances'
            . ' (the same processor can be re-used in several actions or groups):</info> %s',
            count($allProcessorsIds)
        ));

        $output->writeln('<info>Public Actions:</info>');
        foreach ($publicActions as $action) {
            $output->writeln('  ' . $action);
        }

        $output->writeln('');
        $output->writeln(sprintf(
            'To show a list of processors for a specific action, run <info>%1$s ACTION</info>,'
            . ' e.g. <info>%1$s get_list</info>',
            $this->getName()
        ));
    }

    /**
     * @param OutputInterface $output
     * @param array           $processors [action => [number of processors, groups], ...]
     */
    protected function dumpAllActions(OutputInterface $output, array $processors)
    {
        $table = new Table($output);
        $table->setHeaders(['Action', 'Groups', 'Details']);
        $i = 0;
        foreach ($processors as $action => list($numberOfProcessors, $groups)) {
            if ($i > 0) {
                $table->addRow(new TableSeparator());
            }
            $table->addRow([
                $action,
                implode(PHP_EOL, $groups),
                sprintf('Number of processors: %s', $numberOfProcessors)
            ]);
            $i++;
        }
        $table->render();
    }

    /**
     * @param OutputInterface $output
     * @param RequestType     $requestType
     */
    protected function dumpAllProcessors(OutputInterface $output, RequestType $requestType)
    {
        $output->writeln('The processors are displayed in alphabetical order.');

        $table = new Table($output);
        $table->setHeaders(['Processor', 'Actions', 'Is Service?']);

        $context = new Context();
        $context->set(ApiContext::REQUEST_TYPE, $requestType);

        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new Util\RequestTypeApplicableChecker());

        $processorsMap = [];
        $actions = $this->processorBag->getActions();
        foreach ($actions as $action) {
            $context->setAction($action);
            $processors = $this->processorBag->getProcessors($context);
            $processors->setApplicableChecker($applicableChecker);
            foreach ($processors as $processor) {
                if ($processor instanceof TraceableProcessor) {
                    $processor = $processor->getProcessor();
                }
                $className = get_class($processor);
                if (!isset($processorsMap[$className])) {
                    $processorsMap[$className] = [[], false];
                }
                if (!in_array($action, $processorsMap[$className][0], true)) {
                    $processorsMap[$className][0][] = $action;
                }
                if ($this->container->has($processors->getProcessorId())) {
                    $processorsMap[$className][1][] = true;
                }
            }
        }
        ksort($processorsMap);
        foreach ($processorsMap as $className => list($actionNames, $isService)) {
            $isServiceStr = 'No';
            if ($isService) {
                $isServiceStr = 'Yes';
            }
            $table->addRow([$className, implode("\n", $actionNames), $isServiceStr]);
        }

        $table->render();
    }

    /**
     * @param OutputInterface $output
     * @param RequestType     $requestType
     */
    protected function dumpProcessorsWithoutDescription(OutputInterface $output, RequestType $requestType)
    {
        $output->writeln('The list of processors that do not have a description:');

        $context = new Context();
        $context->set(ApiContext::REQUEST_TYPE, $requestType);

        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new Util\RequestTypeApplicableChecker());

        $processorClasses = [];
        $actions = $this->processorBag->getActions();
        foreach ($actions as $action) {
            $context->setAction($action);
            $processors = $this->processorBag->getProcessors($context);
            $processors->setApplicableChecker($applicableChecker);
            foreach ($processors as $processor) {
                if ($processor instanceof TraceableProcessor) {
                    $processor = $processor->getProcessor();
                }
                $processorClasses[] = get_class($processor);
            }
        }
        $processorClasses = array_unique($processorClasses);
        sort($processorClasses);
        foreach ($processorClasses as $processorClass) {
            $processorDescription = $this->getClassDocComment($processorClass);
            if (empty($processorDescription)) {
                $output->writeln(' - ' . $processorClass);
            }
        }
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
        $processors = $this->processorBag->getProcessors($context);

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

            $processorColumn = sprintf(
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
            $table->addRow([$processorColumn, $attributesColumn]);
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

        $delimiter = sprintf(' <comment>%s</comment> ', key($value));
        $items = array_map(
            function ($val) {
                if (is_array($val)) {
                    $item = reset($val);

                    return sprintf('<comment>%s</comment>%s', key($val), $item);
                }

                return $val;
            },
            $items
        );

        if ($items <= 3) {
            return implode($delimiter, $items);
        }

        $result = '';
        $chunks = array_chunk($items, 3);
        foreach ($chunks as $chunk) {
            if ($result) {
                $result .= "\n" . $delimiter;
            }
            $result .= implode($delimiter, $chunk);
        }

        return $result;
    }

    /**
     * @param string $action
     *
     * @return string[]
     */
    protected function getProcessorIds($action)
    {
        $context = new Context();
        $context->setAction($action);
        $processors = $this->processorBag->getProcessors($context);
        $processors->setApplicableChecker(new ChainApplicableChecker());

        $result = [];
        foreach ($processors as $processor) {
            $result[] = $processors->getProcessorId();
        }

        return array_unique($result);
    }
}
