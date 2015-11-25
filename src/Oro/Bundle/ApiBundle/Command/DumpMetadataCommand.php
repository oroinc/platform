<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class DumpMetadataCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:metadata:dump')
            ->setDescription('Dumps metadata of API entity.')
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

        $metadata = $this->getMetadata($entityClass, $version, $requestType);
        $output->write(Yaml::dump($metadata, 100));
    }

    /**
     * @param string $entityClass
     * @param string $version
     * @param string $requestType
     *
     * @return array
     */
    protected function getMetadata($entityClass, $version, $requestType)
    {
        /** @var MetadataProvider $configProvider */
        $metadataProvider = $this->getContainer()->get('oro_api.metadata_provider');
        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.config_provider');

        $metadata = $metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            [],
            $configProvider->getConfig($entityClass, $version, $requestType)
        );

        return [
            'oro_api' => [
                'metadata' => [
                    $entityClass => [
                        $version => null !== $metadata ? $metadata->toArray() : null
                    ]
                ]
            ]
        ];
    }
}
