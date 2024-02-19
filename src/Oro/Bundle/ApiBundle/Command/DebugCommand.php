<?php
declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\AbstractMatcher as Matcher;
use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
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

/**
 * Displays registered API actions and processors.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DebugCommand extends AbstractDebugCommand
{
    private const MAX_ELEMENTS_PER_LINE = 2;
    private const HIDDEN_ACTIONS = [
        'customize_loaded_data.identifier_only'
    ];

    /** @var string */
    protected static $defaultName = 'oro:api:debug';

    private ActionProcessorBagInterface $actionProcessorBag;
    private ProcessorBagInterface $processorBag;

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
     * {@inheritdoc }
     */
    protected function configure(): void
    {
        $this
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
                'Shows processors executed when the given attribute value is present'
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
                'Show a list of all processors without descriptions'
            )
            ->addOption(
                'no-docs',
                null,
                InputOption::VALUE_NONE,
                'Do not show descriptions of API processors'
            )
            ->setDescription('Displays registered API actions and processors.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays a list of available API actions.

  <info>php %command.full_name%</info>

To see the processors registered for a given action, specify the action name as an argument:

  <info>php %command.full_name% <action></info>
  <info>php %command.full_name% --no-docs <action></info>

The list of the processors can be limited to some group specified as the second argument:

  <info>php %command.full_name% <action> <group></info>
  <info>php %command.full_name% --no-docs <action> <group></info>

The <info>--attribute</info> option can be used to show the processors that will be executed
only when the context has a given attribute with the specified value.
The attribute name and value should be separated by a colon, e.g. <info>--attribute=collection:true</info>
for a scalar value, or <info>--attribute=extra:[definition,filters]</info> for an array value:

  <info>php %command.full_name% --attribute=collection:true <action></info>
  <info>php %command.full_name% --attribute=extra:[definition,filters] <action></info>

The <info>--processors</info> and <info>--processors-without-description</info> options can be used
to display all processors and all processors without descriptions respectively:

  <info>php %command.full_name% --processors</info>
  <info>php %command.full_name% --processors-without-description</info>

HELP
            )
            ->addUsage('<action>')
            ->addUsage('<action> <group>')
            ->addUsage('--attribute=collection:true <action>')
            ->addUsage('--attribute=extra:[definition,filters] <action>')
            ->addUsage('--processors')
            ->addUsage('--processors-without-description');

        parent::configure();
    }

    /**
     * {@inheritdoc }
     */
    protected function getDefaultRequestType(): array
    {
        return ['any'];
    }

    /**
     * {@inheritdoc }
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $showProcessors = $input->getOption('processors');
        if ($showProcessors) {
            $this->dumpAllProcessors($output, $this->getRequestType($input));

            return 0;
        }

        $showProcessorsWithoutDescription = $input->getOption('processors-without-description');
        if ($showProcessorsWithoutDescription) {
            $this->dumpProcessorsWithoutDescription($output, $this->getRequestType($input));

            return 0;
        }

        $action = $input->getArgument('action');
        if (empty($action)) {
            $this->dumpActions($output);

            return 0;
        }

        /** @var string[] $attributes */
        $attributes = $input->getOption('attribute');
        $group = $input->getArgument('group');
        if ($group) {
            $attributes[] = sprintf('group:%s', $group);
        }
        $this->dumpProcessors(
            $output,
            $action,
            $this->getRequestType($input),
            $attributes,
            $input->getOption('no-docs')
        );

        return 0;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function dumpActions(OutputInterface $output): void
    {
        $publicActions = $this->getPublicActions();

        $processorsForPublicActions = [];
        $processorsForOtherActions = [];
        $totalNumberOfProcessors = 0;
        $allProcessorsIds = [];
        foreach ($this->processorBag->getActions() as $action) {
            if (\in_array($action, self::HIDDEN_ACTIONS, true)) {
                continue;
            }
            $processorIds = $this->getProcessorIds($action);
            $allProcessorsIds[] = $processorIds;
            $numberOfProcessors = count($processorIds);
            $totalNumberOfProcessors += $numberOfProcessors;
            $action = $this->resolveAction($action);
            $processorGroups = $this->processorBag->getActionGroups($action);
            if (\in_array($action, $publicActions, true)) {
                $processorsForPublicActions[$action] = [$numberOfProcessors, $processorGroups];
            } elseif (isset($processorsForOtherActions[$action])) {
                $processorsForOtherActions[$action][0] += $numberOfProcessors;
                $processorsForOtherActions[$action][1] += array_merge(
                    $processorsForOtherActions[$action][1],
                    $processorGroups
                );
            } else {
                $processorsForOtherActions[$action] = [$numberOfProcessors, $processorGroups];
            }
        }
        $allProcessorsIds = array_unique(array_merge(...$allProcessorsIds));
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
    private function dumpAllActions(OutputInterface $output, array $processors): void
    {
        $table = new Table($output);
        $table->setHeaders(['Action', 'Groups', 'Details']);
        $i = 0;
        foreach ($processors as $action => [$numberOfProcessors, $groups]) {
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

    private function dumpAllProcessors(OutputInterface $output, RequestType $requestType): void
    {
        $output->writeln('The processors are displayed in alphabetical order.');

        $table = new Table($output);
        $table->setHeaders(['Processor', 'Actions']);

        $context = new Context();
        $context->set(ApiContext::REQUEST_TYPE, $requestType);

        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new Util\RequestTypeApplicableChecker());

        $processorsMap = [];
        $actions = $this->processorBag->getActions();
        foreach ($actions as $action) {
            if (\in_array($action, self::HIDDEN_ACTIONS, true)) {
                continue;
            }
            $context->setAction($action);
            $processors = $this->processorBag->getProcessors($context);
            $processors->setApplicableChecker($applicableChecker);
            foreach ($processors as $processor) {
                if ($processor instanceof TraceableProcessor) {
                    $processor = $processor->getProcessor();
                }
                $className = get_class($processor);
                if (!isset($processorsMap[$className])) {
                    $processorsMap[$className] = [];
                }
                $action = $this->resolveAction($action);
                if (!in_array($action, $processorsMap[$className], true)) {
                    $processorsMap[$className][] = $action;
                }
            }
        }
        ksort($processorsMap);
        foreach ($processorsMap as $className => $actionNames) {
            $table->addRow([$className, implode("\n", $actionNames)]);
        }

        $table->render();
    }

    private function dumpProcessorsWithoutDescription(OutputInterface $output, RequestType $requestType): void
    {
        $output->writeln('The list of processors that do not have a description:');

        $context = new Context();
        $context->set(ApiContext::REQUEST_TYPE, $requestType);

        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new Util\RequestTypeApplicableChecker());

        $processorClasses = [];
        $actions = $this->processorBag->getActions();
        foreach ($actions as $action) {
            if (\in_array($action, self::HIDDEN_ACTIONS, true)) {
                continue;
            }
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
     * @param bool            $noDocs
     */
    private function dumpProcessors(
        OutputInterface $output,
        string $action,
        RequestType $requestType,
        array $attributes,
        bool $noDocs
    ) {
        $output->writeln('The processors are displayed in the order they are executed.');
        $table = new Table($output);
        $table->setHeaders(['Processor', 'Attributes']);

        $actionMap = [];
        $existingActions = $this->processorBag->getActions();
        foreach ($existingActions as $existingAction) {
            $actionMap[$this->resolveAction($existingAction)][] = $existingAction;
        }

        $tableRowCount = 0;
        $targetActions = $actionMap[$action] ?? [];
        foreach ($targetActions as $targetAction) {
            $context = new Context();
            $context->setAction($targetAction);
            $context->set(ApiContext::REQUEST_TYPE, $requestType);
            $specifiedAttributes = [];
            foreach ($attributes as $attribute) {
                [$name, $value] = explode(':', $attribute, 2);
                $value = $this->getTypedValue($value);
                if ('group' === $name) {
                    $context->setFirstGroup($value);
                    $context->setLastGroup($value);
                } else {
                    $context->set($name, $value);
                    $specifiedAttributes[] = $name;
                }
            }
            $applicableChecker = new ChainApplicableChecker();
            $applicableChecker->addChecker(new Util\RequestTypeApplicableChecker());
            $applicableChecker->addChecker(new Util\AttributesApplicableChecker($specifiedAttributes));
            if (!\in_array($targetAction, self::HIDDEN_ACTIONS, true)) {
                $this->dumpDetailsAboutProcessors($table, $tableRowCount, $context, $applicableChecker, $noDocs);
            }
        }

        $table->render();
    }

    private function dumpDetailsAboutProcessors(
        Table $table,
        int &$tableRowCount,
        Context $context,
        ApplicableCheckerInterface $applicableChecker,
        bool $noDocs
    ): void {
        $action = $context->getAction();
        $processors = $this->processorBag->getProcessors($context);
        $processors->setApplicableChecker($applicableChecker);
        foreach ($processors as $processor) {
            $targetAction = $this->resolveAction($action);
            $processorAttributes = $processors->getProcessorAttributes();
            if ($targetAction !== $action) {
                $processorGroup = substr($action, strpos($action, '.') + 1);
                if ($context->getFirstGroup() && $context->getFirstGroup() !== $processorGroup) {
                    continue;
                }
                $processorAttributes = ['group' => $processorGroup] + $processorAttributes;
            }

            if ($tableRowCount > 0) {
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
            if (!$noDocs) {
                $processorDescription = $this->getClassDocComment(get_class($processor));
                if (!empty($processorDescription)) {
                    $processorColumn .= PHP_EOL . $processorDescription;
                }
            }

            $attributesColumn = $this->formatProcessorAttributes($processorAttributes, $targetAction);
            $table->addRow([$processorColumn, $attributesColumn]);
            $tableRowCount++;
        }
    }

    private function getClassDocComment(string $className): string
    {
        $reflection = new \ReflectionClass($className);

        $comment = $reflection->getDocComment();
        if (false === $comment) {
            return '';
        }

        $comment = preg_replace('/^\s+\* @[\w0-9]+.*/msi', '', $comment);
        $comment = strtr($comment, ['/**' => '', '*/' => '']);
        $comment = preg_replace('/^\s+\* ?/m', '', $comment);

        return trim($comment);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function formatProcessorAttributes(array $attributes, string $action): string
    {
        $rows = [];

        if (array_key_exists('group', $attributes)) {
            $group = $attributes['group'];
            if ('customize_loaded_data' === $action) {
                $rows[] = $this->formatProcessorAttribute('collection', 'collection' === $group, true);
            } elseif ('customize_form_data' === $action) {
                $rows[] = $this->formatProcessorAttribute('event', $group, true);
            } elseif ('normalize_value' === $action) {
                $rows[] = $this->formatProcessorAttribute('dataType', $group, true);
            } else {
                $rows[] = $this->formatProcessorAttribute('group', $group, true);
            }
            unset($attributes['group']);
        }
        if ('get_config' === $action && array_key_exists(FilterIdentifierFieldsConfigExtra::NAME, $attributes)) {
            $identifierFieldsOnly = $attributes[FilterIdentifierFieldsConfigExtra::NAME];
            if (array_key_exists('extra', $attributes)) {
                $extra = $attributes['extra'];
                if (is_string($extra) || key($extra) === Matcher::OPERATOR_NOT) {
                    $extra = [Matcher::OPERATOR_AND => [$extra]];
                }
                if ($identifierFieldsOnly) {
                    $extra[Matcher::OPERATOR_AND][] = FilterIdentifierFieldsConfigExtra::NAME;
                } else {
                    $extra[Matcher::OPERATOR_AND][] = [
                        Matcher::OPERATOR_NOT => FilterIdentifierFieldsConfigExtra::NAME
                    ];
                }
            } elseif ($identifierFieldsOnly) {
                $extra = FilterIdentifierFieldsConfigExtra::NAME;
            } else {
                $extra = [Matcher::OPERATOR_NOT => FilterIdentifierFieldsConfigExtra::NAME];
            }
            $attributes = ['extra' => $extra] + $attributes;
            unset($attributes[FilterIdentifierFieldsConfigExtra::NAME]);
        }

        foreach ($attributes as $key => $val) {
            $rows[] = $this->formatProcessorAttribute($key, $val);
        }

        return implode(PHP_EOL, $rows);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param bool   $bold
     *
     * @return string
     */
    private function formatProcessorAttribute(string $name, $value, $bold = false): string
    {
        $stringValue = $this->convertProcessorAttributeValueToString($value);
        if ($bold) {
            $stringValue = sprintf('<comment>%s</comment>', $stringValue);
        }

        return implode(': ', [$name, $stringValue]);
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function convertProcessorAttributeValueToString($value): string
    {
        if (null === $value) {
            return '<comment>!exists</comment>';
        }

        if (!is_array($value)) {
            return $this->convertValueToString($value);
        }

        $items = reset($value);
        if (!is_array($items)) {
            if (null === $items && key($value) === Matcher::OPERATOR_NOT) {
                return '<comment>exists</comment>';
            }

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

        if ($items <= self::MAX_ELEMENTS_PER_LINE) {
            return implode($delimiter, $items);
        }

        $result = '';
        $chunks = array_chunk($items, self::MAX_ELEMENTS_PER_LINE);
        foreach ($chunks as $chunk) {
            if ($result) {
                $result .= "\n" . $delimiter;
            }
            $result .= implode($delimiter, $chunk);
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getPublicActions(): array
    {
        $publicActions = $this->actionProcessorBag->getActions();
        unset($publicActions[array_search('unhandled_error', $publicActions, true)]);

        return array_values($publicActions);
    }

    /**
     * @return string[]
     */
    private function getProcessorIds(string $action): array
    {
        $context = new Context();
        $context->setAction($action);
        $processors = $this->processorBag->getProcessors($context);
        $processors->setApplicableChecker(new ChainApplicableChecker());

        $result = [];
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($processors as $processor) {
            $result[] = $processors->getProcessorId();
        }

        return array_unique($result);
    }

    private function resolveAction(string $action): string
    {
        $delimiterPas = strpos($action, '.');
        if (false === $delimiterPas) {
            return $action;
        }

        return substr($action, 0, $delimiterPas);
    }
}
