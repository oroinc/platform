<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;

class DumpMetadataCommand extends AbstractDebugCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:metadata:dump')
            ->setDescription('Dumps entity metadata used in Data API.')
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
                'action',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of action for which the metadata should be displayed.' .
                'Can be "get", "get_list", "create", "update", "delete", "delete_list", etc.'.
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
        $entityClass = $this->resolveEntityClass($input->getArgument('entity'), $requestType);
        // @todo: API version is not supported for now
        //$version = $input->getArgument('version');
        $version = Version::LATEST;
        $action = $input->getOption('action');

        /** @var ProcessorBag $processorBag */
        $processorBag = $this->getContainer()->get('oro_api.processor_bag');
        $processorBag->addApplicableChecker(new Util\RequestTypeApplicableChecker());

        $metadata = $this->getMetadata($entityClass, $version, $requestType, $action);
        $output->write(Yaml::dump($metadata, 100, 4, true, true));
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $action
     *
     * @return array
     */
    protected function getMetadata($entityClass, $version, RequestType $requestType, $action)
    {
        /** @var MetadataProvider $configProvider */
        $metadataProvider = $this->getContainer()->get('oro_api.metadata_provider');
        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.config_provider');

        $configExtras = [
            new EntityDefinitionConfigExtra($action)
        ];

        $config   = $configProvider->getConfig($entityClass, $version, $requestType, $configExtras);
        $metadata = $metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            [],
            $config->getDefinition()
        );

        return [
            'oro_api' => [
                'metadata' => [
                    $entityClass => null !== $metadata ? $metadata->toArray() : null
                ]
            ]
        ];
    }
}
