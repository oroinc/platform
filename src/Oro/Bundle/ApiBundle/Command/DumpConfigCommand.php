<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\VirtualFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class DumpConfigCommand extends ContainerAwareCommand
{
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
                'request-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The request type'
            )
            ->addOption(
                'section',
                null,
                InputOption::VALUE_REQUIRED,
                'The configuration section. Can be "entities" or "relations"',
                'entities'
            )
            ->addOption(
                'without-virtual-fields',
                null,
                InputOption::VALUE_NONE,
                'Whether virtual fields should not be added'
            )
            ->addOption(
                'with-descriptions',
                null,
                InputOption::VALUE_NONE,
                'Whether human-readable descriptions should be added'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityClassNameHelper $entityClassNameHelper */
        $entityClassNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');

        $entityClass = $entityClassNameHelper->resolveEntityClass($input->getArgument('entity'), true);
        $requestType = new RequestType($input->getOption('request-type'));
        // @todo: API version is not supported for now
        //$version     = $input->getArgument('version');
        $version = Version::LATEST;

        $extras = [new FiltersConfigExtra(), new SortersConfigExtra()];
        if (!$input->getOption('without-virtual-fields')) {
            $extras[] = new VirtualFieldsConfigExtra();
        }
        if ($input->getOption('with-descriptions')) {
            $extras[] = new DescriptionsConfigExtra();
        }

        /** @var ProcessorBag $processorBag */
        $processorBag = $this->getContainer()->get('oro_api.processor_bag');
        $processorBag->addApplicableChecker(new RequestTypeApplicableChecker());

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
        foreach ([ConfigUtil::DEFINITION, ConfigUtil::FILTERS, ConfigUtil::SORTERS] as $sectionName) {
            if (array_key_exists($sectionName, $data)) {
                $result[$sectionName] = $data[$sectionName];
            }
        }
        // add other sections
        foreach ($data as $sectionName => $config) {
            if (!array_key_exists($sectionName, $result)) {
                $result[$sectionName] = $config;
            }
        }

        return $result;
    }
}
