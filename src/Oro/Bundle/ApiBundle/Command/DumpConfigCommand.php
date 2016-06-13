<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class DumpConfigCommand extends AbstractDebugCommand
{
    /**
     * @var array
     */
    protected $knownExtras = [
        'Oro\Bundle\ApiBundle\Config\ActionsConfigExtra',
        'Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra',
        'Oro\Bundle\ApiBundle\Config\FiltersConfigExtra',
        'Oro\Bundle\ApiBundle\Config\SortersConfigExtra',
        'Oro\Bundle\ApiBundle\Config\VirtualFieldsConfigExtra',
        'Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra',
        'Oro\Bundle\ApiBundle\Config\StatusCodesConfigExtra',
        'Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra',
        'Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:config:dump')
            ->setDescription('Dumps entity configuration used in Data API.')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'The entity class name or alias'
            )
            // @todo: API version is not supported for now
            //->addArgument(
            //    'version',
            //    InputArgument::OPTIONAL,
            //    'API version',
            //    Version::LATEST
            //)
            ->addOption(
                'section',
                null,
                InputOption::VALUE_REQUIRED,
                'The configuration section. Can be "entities" or "relations"',
                'entities'
            )
            ->addOption(
                'extra',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The kind of configuration data that should be displayed. ' .
                sprintf(
                    'Can be %s or the full name of a class implements "%s"',
                    '"' . implode('", "', array_keys($this->getKnownExtras())) . '"',
                    'Oro\Bundle\ApiBundle\Config\ConfigExtraInterface'
                ),
                ['definition', 'filters', 'sorters']
            )
            ->addOption(
                'action',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of action for which the configuration should be displayed.' .
                'Can be "get", "get_list", "create", "update", "delete", "delete_list", etc.',
                'get'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $requestType = $this->getRequestType($input);
        // @todo: API version is not supported for now
        //$version = $input->getArgument('version');
        $version = Version::LATEST;
        $extras = $this->getConfigExtras($input);

        /** @var ProcessorBag $processorBag */
        $processorBag = $this->getContainer()->get('oro_api.processor_bag');
        $processorBag->addApplicableChecker(new Util\RequestTypeApplicableChecker());

        $entityClass = $this->resolveEntityClass($input->getArgument('entity'), $requestType);

        switch ($input->getOption('section')) {
            case 'entities':
                $config = $this->getConfig($entityClass, $version, $requestType, $extras);
                break;
            case 'relations':
                $config = $this->getRelationConfig($entityClass, $version, $requestType, $extras);
                break;
            default:
                throw new \InvalidArgumentException(
                    'The section should be either "entities" or "relations".'
                );
        }

        array_walk_recursive(
            $config,
            function (&$val) {
                if ($val instanceof \Closure) {
                    $val = '\Closure';
                }
            }
        );
        $output->write(Yaml::dump($config, 100, 4, true, true));
    }

    /**
     * @param InputInterface $input
     *
     * @return array
     */
    protected function getConfigExtras(InputInterface $input)
    {
        $result = [];

        $knownExtras = $this->getKnownExtras();
        $extraNames = $input->getOption('extra');
        foreach ($extraNames as $extraName) {
            $extraClassName = null;
            if (array_key_exists($extraName, $knownExtras)) {
                $extraClassName = $knownExtras[$extraName];
            } else {
                if (false === strpos($extraName, '\\')) {
                    throw new \InvalidArgumentException(
                        sprintf('Unknown value "%s" for the "--extra" option.', $extraName)
                    );
                }
                if (!class_exists($extraName)) {
                    throw new \InvalidArgumentException(
                        sprintf('The class "%s" passed as value for the "--extra" option not found.', $extraName)
                    );
                }
                if (!is_a($extraName, 'Oro\Bundle\ApiBundle\Config\ConfigExtraInterface', true)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The class "%s" passed as value for the "--extra" option must implement "%s".',
                            $extraName,
                            'Oro\Bundle\ApiBundle\Config\ConfigExtraInterface'
                        )
                    );
                }
                $extraClassName = $extraName;
            }

            if ('Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra' === $extraClassName) {
                $action = $input->getOption('action');
                $result[] = new $extraClassName($action);
            } else {
                $result[] = new $extraClassName();
            }
        }

        return $result;
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $extras
     *
     * @return array
     */
    protected function getConfig($entityClass, $version, RequestType $requestType, array $extras)
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.config_provider');

        $config = $configProvider->getConfig($entityClass, $version, $requestType, $extras);

        return [
            'oro_api' => [
                'entities' => [
                    $entityClass => $this->convertConfigToArray($config)
                ]
            ]
        ];
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $extras
     *
     * @return array
     */
    protected function getRelationConfig($entityClass, $version, RequestType $requestType, array $extras)
    {
        /** @var RelationConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.relation_config_provider');

        $config = $configProvider->getRelationConfig($entityClass, $version, $requestType, $extras);

        return [
            'oro_api' => [
                'relations' => [
                    $entityClass => $this->convertConfigToArray($config)
                ]
            ]
        ];
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    protected function convertConfigToArray(Config $config)
    {
        $result = [];

        $data = $config->toArray();

        // add known sections in predefined order
        if (!empty($data[ConfigUtil::DEFINITION])) {
            $result = $data[ConfigUtil::DEFINITION];
        }
        unset($data[ConfigUtil::DEFINITION]);
        foreach ([ConfigUtil::FILTERS, ConfigUtil::SORTERS] as $sectionName) {
            if (array_key_exists($sectionName, $data)) {
                $result[$sectionName] = $data[$sectionName];
            }
        }
        // add other sections
        foreach ($data as $sectionName => $sectionConfig) {
            if (!array_key_exists($sectionName, $result)) {
                $result[$sectionName] = $sectionConfig;
            }
        }

        return $result;
    }

    /**
     * @return string[] [extra name => extra class name]
     */
    protected function getKnownExtras()
    {
        $result = [];
        foreach ($this->knownExtras as $className) {
            $result[constant($className . '::NAME')] = $className;
        }

        return $result;
    }
}
