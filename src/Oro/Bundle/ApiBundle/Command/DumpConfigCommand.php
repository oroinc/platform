<?php
declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use ProxyManager\Proxy\VirtualProxyInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Dumps entity configuration used in API.
 */
class DumpConfigCommand extends AbstractDebugCommand
{
    /** @var string */
    protected static $defaultName = 'oro:api:config:dump';

    private ProcessorBagInterface $processorBag;
    private ConfigProvider $configProvider;

    private const KNOWN_EXTRAS = [
        EntityDefinitionConfigExtra::class,
        FiltersConfigExtra::class,
        SortersConfigExtra::class,
        DescriptionsConfigExtra::class,
        CustomizeLoadedDataConfigExtra::class,
        DataTransformersConfigExtra::class
    ];

    public function __construct(
        ValueNormalizer $valueNormalizer,
        ResourcesProvider $resourcesProvider,
        ProcessorBagInterface $processorBag,
        ConfigProvider $configProvider
    ) {
        parent::__construct($valueNormalizer, $resourcesProvider);

        $this->processorBag = $processorBag;
        $this->configProvider = $configProvider;
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'entity',
                InputArgument::OPTIONAL,
                'Entity class name or alias'
            )
            ->addOption(
                'extra',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Configuration kind',
                ['definition', 'filters', 'sorters', 'customize_loaded_data', 'data_transformers']
            )
            ->addOption(
                'action',
                null,
                InputOption::VALUE_REQUIRED,
                'Action name',
                ApiAction::GET_LIST
            )
            ->addOption(
                'documentation-resources',
                null,
                InputOption::VALUE_NONE,
                'Show a list of documentation resources'
            )
            ->setDescription('Dumps entity configuration used in API.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command dumps the given entity configuration used in API.

  <info>php %command.full_name% <entity></info>

The <info>--extra</info> option can be used to limit the scope of the dumped configuration.
Accepted values are <info>filters</info>, <info>sorters</info>, <info>customize_loaded_data</info>, <info>data_transformers</info>, etc.
or the full class name(s) of the respective implementations of
<comment>\Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface</comment> interface.
The <info>--extra=definition</info> should always be specified in addition to any other extras:

  <info>php %command.full_name% --extra=definition --extra=filters <entity></info>
  <info>php %command.full_name% --extra=definition --extra=data_transformers <entity></info>

The <info>--action</info> option can be used to specify the name of the action
for which the configuration is requested. Accepted values are: <comment>options</comment>,
<comment>get</comment>, <comment>get_list</comment>, <comment>update</comment>, <comment>update_list</comment>, <comment>create</comment>, <comment>delete</comment>, <comment>delete_list</comment>,
<comment>get_subresource</comment>, <comment>update_subresource</comment>, <comment>add_subresource</comment>, <comment>delete_subresource</comment>,
<comment>get_relationship</comment>, <comment>update_relationship</comment>, <comment>add_relationship</comment>, <comment>delete_relationship</comment>.

  <info>php %command.full_name% --action=<action> <entity></info>

The <info>--documentation-resources</info> option shows a list of documentation resources:

  <info>php %command.full_name% --documentation-resources <entity></info>

HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--extra=definition --extra=<extra> <entity>')
            ->addUsage('--extra=definition --extra=<extra1> --extra=<extra2> --extra=<extraN> <entity>')
            ->addUsage('--action=<action> <entity>')
            ->addUsage('--documentation-resources <entity>')
        ;

        parent::configure();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $requestType = $this->getRequestType($input);
        // API version is not supported for now
        $version = Version::normalizeVersion(null);
        $extras = $this->getConfigExtras($input);

        $this->processorBag->addApplicableChecker(new Util\RequestTypeApplicableChecker());

        $entityClass = $this->resolveEntityClass($input->getArgument('entity'), $version, $requestType);
        $isDocumentationResourcesRequested = $input->getOption('documentation-resources');
        if ($isDocumentationResourcesRequested && !$entityClass) {
            $entityClasses = $this->getEntityClasses($version, $requestType);
            foreach ($entityClasses as $entityClass) {
                $config = $this->getConfig($entityClass, $version, $requestType, $extras);
                $config = $this->getDocumentationResources($config);
                $this->dumpConfig($output, $config);
            }
        } else {
            if (!$entityClass) {
                throw new RuntimeException('The "entity" argument is missing.');
            }

            $config = $this->getConfig($entityClass, $version, $requestType, $extras);
            if ($isDocumentationResourcesRequested) {
                $config = $this->getDocumentationResources($config);
            }

            $this->dumpConfig($output, $config);
        }

        return 0;
    }

    protected function getConfigExtras(InputInterface $input): array
    {
        $result = [];

        $knownExtras = $this->getKnownExtras();
        $extraNames = $input->getOption('extra');
        foreach ($extraNames as $extraName) {
            $extraClassName = null;
            if (\array_key_exists($extraName, $knownExtras)) {
                $extraClassName = $knownExtras[$extraName];
            } else {
                if (!str_contains($extraName, '\\')) {
                    throw new \InvalidArgumentException(sprintf(
                        'Unknown value "%s" for the "--extra" option.',
                        $extraName
                    ));
                }
                if (!class_exists($extraName)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The class "%s" passed as value for the "--extra" option not found.',
                        $extraName
                    ));
                }
                if (!is_a($extraName, ConfigExtraInterface::class, true)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The class "%s" passed as value for the "--extra" option must implement "%s".',
                        $extraName,
                        ConfigExtraInterface::class
                    ));
                }
                $extraClassName = $extraName;
            }

            if (EntityDefinitionConfigExtra::class === $extraClassName) {
                $action = $input->getOption('action');
                $result[] = new $extraClassName($action);
            } else {
                $result[] = new $extraClassName();
            }
        }

        return $result;
    }

    protected function getConfig(string $entityClass, string $version, RequestType $requestType, array $extras): array
    {
        $config = $this->configProvider->getConfig($entityClass, $version, $requestType, $extras);

        return [
            $entityClass => $this->convertConfigToArray($config)
        ];
    }

    protected function getEntityClasses(string $version, RequestType $requestType): array
    {
        $resources = $this->resourcesProvider->getResources($version, $requestType);
        $entityClasses = [];
        foreach ($resources as $resource) {
            $entityClasses[] = $resource->getEntityClass();
        }
        sort($entityClasses);

        return $entityClasses;
    }

    protected function convertConfigToArray(Config $config): array
    {
        $result = [];

        $data = $config->toArray();

        // add known sections in predefined order
        if (!empty($data[ConfigUtil::DEFINITION])) {
            $result = $data[ConfigUtil::DEFINITION];
        }
        unset($data[ConfigUtil::DEFINITION]);
        foreach ([ConfigUtil::FILTERS, ConfigUtil::SORTERS] as $sectionName) {
            if (\array_key_exists($sectionName, $data)) {
                $result[$sectionName] = $data[$sectionName];
            }
        }
        // add other sections
        foreach ($data as $sectionName => $sectionConfig) {
            if (!\array_key_exists($sectionName, $result)) {
                $result[$sectionName] = $sectionConfig;
            }
        }

        return $result;
    }

    /**
     * @return string[] [extra name => extra class name]
     */
    protected function getKnownExtras(): array
    {
        $result = [];
        foreach (self::KNOWN_EXTRAS as $className) {
            $result[\constant($className . '::NAME')] = $className;
        }

        return $result;
    }

    protected function convertConfigValueToHumanReadableRepresentation(mixed $val): mixed
    {
        if ($val instanceof \Closure) {
            $val = sprintf(
                'closure from %s',
                (new \ReflectionFunction($val))->getClosureScopeClass()->getName()
            );
        } elseif (\is_object($val)) {
            if ($val instanceof VirtualProxyInterface) {
                if (!$val->isProxyInitialized()) {
                    $val->initializeProxy();
                }
                $val = $val->getWrappedValueHolderValue();
            }
            $val = sprintf('instance of %s', \get_class($val));
        }

        return $val;
    }

    private function dumpConfig(OutputInterface $output, array $config): void
    {
        array_walk_recursive(
            $config,
            function (&$val) {
                $val = $this->convertConfigValueToHumanReadableRepresentation($val);
            }
        );
        $output->write(Yaml::dump($config, 100, 4, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE | Yaml::DUMP_OBJECT));
    }

    private function getDocumentationResources(array $config): array
    {
        $keys = array_keys($config);
        $entityClass = reset($keys);
        $config = $config[$entityClass];
        $documentationResource = [];
        if (\array_key_exists(ConfigUtil::DOCUMENTATION_RESOURCE, $config)) {
            $documentationResource = $config[ConfigUtil::DOCUMENTATION_RESOURCE];
        }

        return [
            $entityClass => [
                ConfigUtil::DOCUMENTATION_RESOURCE => $documentationResource
            ]
        ];
    }
}
