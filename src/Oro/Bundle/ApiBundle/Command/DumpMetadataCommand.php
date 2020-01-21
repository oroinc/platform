<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessor;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * The CLI command to show metadata of API resources.
 */
class DumpMetadataCommand extends AbstractDebugCommand
{
    /** @var string */
    protected static $defaultName = 'oro:api:metadata:dump';

    /** @var ProcessorBagInterface */
    private $processorBag;

    /** @var MetadataProvider */
    private $metadataProvider;

    /** @var ConfigProvider */
    private $configProvider;

    /**
     * @param ValueNormalizer $valueNormalizer
     * @param ResourcesProvider $resourcesProvider
     * @param ProcessorBagInterface $processorBag
     * @param MetadataProvider $metadataProvider
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        ResourcesProvider $resourcesProvider,
        ProcessorBagInterface $processorBag,
        MetadataProvider $metadataProvider,
        ConfigProvider $configProvider
    ) {
        parent::__construct($valueNormalizer, $resourcesProvider);

        $this->processorBag = $processorBag;
        $this->metadataProvider = $metadataProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dumps entity metadata used in API.')
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
                ApiAction::GET_LIST
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

        $this->processorBag->addApplicableChecker(new Util\RequestTypeApplicableChecker());

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
        $configExtras = [
            new EntityDefinitionConfigExtra($action)
        ];
        $metadataExtras = [
            new ActionMetadataExtra($action)
        ];
        if ($hateoas) {
            $metadataExtras[] = new HateoasMetadataExtra(new FilterValueAccessor());
        }

        $config   = $this->configProvider->getConfig($entityClass, $version, $requestType, $configExtras);
        $metadata = $this->metadataProvider->getMetadata(
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
