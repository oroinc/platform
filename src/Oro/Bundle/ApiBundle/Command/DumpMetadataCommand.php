<?php
declare(strict_types=1);

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
 * Dumps API entity metadata.
 */
class DumpMetadataCommand extends AbstractDebugCommand
{
    /** @var string */
    protected static $defaultName = 'oro:api:metadata:dump';

    private ProcessorBagInterface $processorBag;
    private MetadataProvider $metadataProvider;
    private ConfigProvider $configProvider;

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

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity class name or alias')
            ->addOption('action', null, InputOption::VALUE_REQUIRED, 'Action name', ApiAction::GET_LIST)
            ->addOption('hateoas', null, InputOption::VALUE_NONE, 'Add HATEOAS links')
            ->setDescription('Dumps API entity metadata.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command dumps API metadata for a given entity.

  <info>php %command.full_name% <entity></info>

The <info>--action</info> option can be used to specify the name of the action
for which the metadata is requested. Accepted values are: <comment>options</comment>,
<comment>get</comment>, <comment>get_list</comment>, <comment>update</comment>, <comment>update_list</comment>, <comment>create</comment>, <comment>delete</comment>, <comment>delete_list</comment>,
<comment>get_subresource</comment>, <comment>update_subresource</comment>, <comment>add_subresource</comment>, <comment>delete_subresource</comment>,
<comment>get_relationship</comment>, <comment>update_relationship</comment>, <comment>add_relationship</comment>, <comment>delete_relationship</comment>.

  <info>php %command.full_name% --action=<action> <entity></info>

The <info>--hateoas</info> option can be used to include the HATEOAS links to the metadata:

  <info>php %command.full_name% --hateoas <entity></info>

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
        $action = $input->getOption('action');
        $hateoas = $input->getOption('hateoas');

        $this->processorBag->addApplicableChecker(new Util\RequestTypeApplicableChecker());

        $entityClass = $this->resolveEntityClass($input->getArgument('entity'), $version, $requestType);

        $metadata = $this->getMetadata($entityClass, $version, $requestType, $action, $hateoas);
        $output->write(Yaml::dump($metadata, 100, 4, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE | Yaml::DUMP_OBJECT));

        return 0;
    }

    protected function getMetadata(
        string $entityClass,
        string $version,
        RequestType $requestType,
        string $action,
        bool $hateoas
    ): array {
        $configExtras = [
            new EntityDefinitionConfigExtra($action)
        ];
        $metadataExtras = [
            new ActionMetadataExtra($action)
        ];
        if ($hateoas) {
            $metadataExtras[] = new HateoasMetadataExtra(new FilterValueAccessor());
        }

        $config = $this->configProvider->getConfig($entityClass, $version, $requestType, $configExtras);
        $metadata = $this->metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            $config->getDefinition(),
            $metadataExtras
        );

        return [
            $entityClass => $metadata?->toArray()
        ];
    }
}
