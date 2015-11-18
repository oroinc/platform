<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class DumpConfigCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:config:debug')
            ->setDescription('Dumps API configuration.')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'The entity class name or alias'
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'API version',
                Version::LATEST
            )
            ->addOption(
                'requestType',
                null,
                InputOption::VALUE_OPTIONAL,
                'API request type',
                RequestType::REST
            )
            ->addOption(
                'section',
                null,
                InputOption::VALUE_OPTIONAL,
                'The configuration section. Can be "entities" or "relations"',
                'entities'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityClassNameHelper $entityClassNameHelper */
        $entityClassNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');

        $entityClass = $entityClassNameHelper->resolveEntityClass($input->getArgument('entity'));
        $version     = $input->getArgument('version');
        $requestType = $input->getOption('requestType');

        switch ($input->getOption('section')) {
            case 'entities':
                $config = $this->getConfig($entityClass, $version, $requestType);
                break;
            case 'relations':
                $config = $this->getRelationConfig($entityClass, $version, $requestType);
                break;
            default:
                throw new \InvalidArgumentException(
                    'The section should be either "entities" or "relations".'
                );
        }

        $output->write(Yaml::dump($config, 100));
    }

    /**
     * @param string $entityClass
     * @param string $version
     * @param string $requestType
     *
     * @return array
     */
    protected function getConfig($entityClass, $version, $requestType)
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.config_provider');

        $config = $configProvider->getConfig(
            $entityClass,
            $version,
            $requestType,
            [ConfigUtil::FILTERS, ConfigUtil::SORTERS]
        );

        return [
            'oro_api' => [
                'entities' => [
                    $entityClass => [
                        $version => $config
                    ]
                ]
            ]
        ];
    }

    /**
     * @param string $entityClass
     * @param string $version
     * @param string $requestType
     *
     * @return array
     */
    protected function getRelationConfig($entityClass, $version, $requestType)
    {
        /** @var RelationConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.relation_config_provider');

        $config = $configProvider->getRelationConfig(
            $entityClass,
            $version,
            $requestType,
            [ConfigUtil::FILTERS, ConfigUtil::SORTERS]
        );

        return [
            'oro_api' => [
                'relations' => [
                    $entityClass => [
                        $version => $config
                    ]
                ]
            ]
        ];
    }
}
