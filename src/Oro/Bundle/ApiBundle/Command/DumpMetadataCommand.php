<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Filter\NullFilterValueAccessor;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * The CLI command to show metadata of Data API resources.
 */
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
            ->addOption(
                'action',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of action for which the metadata should be displayed.' .
                'Can be "get", "get_list", "create", "update", "delete", "delete_list", etc.'.
                'get'
            )
            ->addOption(
                'hateoas',
                null,
                InputOption::VALUE_NONE,
                'Adds HATEOAS related links to the metadata.'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $requestType = $this->getRequestType($input);
        // API version is not supported for now
        $version = Version::normalizeVersion(null);
        $action = $input->getOption('action');
        $hateoas = $input->getOption('hateoas');

        /** @var ProcessorBagInterface $processorBag */
        $processorBag = $this->getContainer()->get('oro_api.processor_bag');
        $processorBag->addApplicableChecker(new Util\RequestTypeApplicableChecker());

        $entityClass = $this->resolveEntityClass($input->getArgument('entity'), $version, $requestType);

        $metadata = $this->getMetadata($entityClass, $version, $requestType, $action, $hateoas);
        $output->write(Yaml::dump($metadata, 100, 4, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE | Yaml::DUMP_OBJECT));
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $action
     * @param bool        $hateoas
     *
     * @return array
     */
    protected function getMetadata($entityClass, $version, RequestType $requestType, $action, $hateoas)
    {
        /** @var MetadataProvider $configProvider */
        $metadataProvider = $this->getContainer()->get('oro_api.metadata_provider');
        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.config_provider');

        $configExtras = [
            new EntityDefinitionConfigExtra($action)
        ];
        $metadataExtras = [
            new ActionMetadataExtra($action)
        ];
        if ($hateoas) {
            $metadataExtras[] = new HateoasMetadataExtra(new NullFilterValueAccessor());
        }

        $config   = $configProvider->getConfig($entityClass, $version, $requestType, $configExtras);
        $metadata = $metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            $config->getDefinition(),
            $metadataExtras
        );

        return [
            $entityClass => null !== $metadata ? $metadata->toArray() : null
        ];
    }
}
