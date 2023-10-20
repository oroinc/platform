<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps all resources accessible through API.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DumpCommand extends AbstractDebugCommand
{
    /** @var string */
    protected static $defaultName = 'oro:api:dump';

    private SubresourcesProvider $subresourcesProvider;
    private EntityClassProviderInterface $entityClassProvider;
    private ConfigProvider $configProvider;

    public function __construct(
        ValueNormalizer $valueNormalizer,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        EntityClassProviderInterface $entityClassProvider,
        ConfigProvider $configProvider
    ) {
        parent::__construct($valueNormalizer, $resourcesProvider);
        $this->subresourcesProvider = $subresourcesProvider;
        $this->entityClassProvider = $entityClassProvider;
        $this->configProvider = $configProvider;
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'entity',
                InputArgument::OPTIONAL,
                'Entity class or entity type'
            )
            ->addOption(
                'sub-resources',
                null,
                InputOption::VALUE_NONE,
                'Shows sub-resources'
            )
            ->addOption(
                'accessible',
                null,
                InputOption::VALUE_NONE,
                'Show resources that are accessible through API'
            )
            ->addOption(
                'not-accessible',
                null,
                InputOption::VALUE_NONE,
                'Show resources that are not accessible through API'
            )
            ->addOption(
                'action',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Show resources that support the specific API action'
            )
            ->addOption(
                'upsert',
                null,
                InputOption::VALUE_NONE,
                'Show resources that support the upsert operation'
            )
            ->setDescription('Dumps all resources accessible through API.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps all resources accessible through API.

  <info>php %command.full_name%</info>

To see more information about a given entity, specify the entity class name
or entity type as an argument:

  <info>php %command.full_name% <entity></info>

The <info>--sub-resources</info> option will include the sub-resources into the dump:

  <info>php %command.full_name% --sub-resources</info>
  <info>php %command.full_name% --sub-resources <entity></info>

The <info>--accessible</info> option reverses the command behavior and
displays a list of entity classes that are <options=bold>accessible</> through API:

  <info>php %command.full_name% --accessible</info>

The <info>--not-accessible</info> option reverses the command behavior and
displays a list of entity classes that are <options=bold>not accessible</> through API:

  <info>php %command.full_name% --not-accessible</info>

The <info>--action</info> option can be used to displays a list of entity classes that support a specific API action:

  <info>php %command.full_name% --action=update_list</info>

The <info>--upsert</info> option can be used to displays a list of entity classes that support the upsert operation:

  <info>php %command.full_name% --upsert</info>

HELP
            )
            ->addUsage('--sub-resources')
            ->addUsage('--sub-resources <entity>')
            ->addUsage('--accessible')
            ->addUsage('--not-accessible')
            ->addUsage('--action=<action>')
            ->addUsage('--upsert')
        ;

        parent::configure();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('accessible')) {
            $this->dumpAccessibleEntities($input, $output);
        } elseif ($input->getOption('not-accessible')) {
            $this->dumpNotAccessibleEntities($input, $output);
        } else {
            $this->dumpResources($input, $output);
        }

        return Command::SUCCESS;
    }

    public function dumpAccessibleEntities(InputInterface $input, OutputInterface $output): void
    {
        $requestType = $this->getRequestType($input);
        // API version is not supported for now
        $version = Version::normalizeVersion(null);

        $resources = $this->resourcesProvider->getResources($version, $requestType);
        $accessibleEntities = [];
        foreach ($resources as $resource) {
            $accessibleEntities[] = $resource->getEntityClass();
        }

        sort($accessibleEntities);

        foreach ($accessibleEntities as $entityClass) {
            $output->writeln(sprintf('<info>%s</info>', $entityClass));
        }
    }

    public function dumpNotAccessibleEntities(InputInterface $input, OutputInterface $output): void
    {
        $requestType = $this->getRequestType($input);
        // API version is not supported for now
        $version = Version::normalizeVersion(null);

        $resources = $this->resourcesProvider->getResources($version, $requestType);
        $accessibleEntities = [];
        foreach ($resources as $resource) {
            $accessibleEntities[$resource->getEntityClass()] = true;
        }

        $notAccessibleEntities = [];
        $entityClasses = $this->entityClassProvider->getClassNames();
        foreach ($entityClasses as $entityClass) {
            if (!isset($accessibleEntities[$entityClass]) && !isset($notAccessibleEntities[$entityClass])) {
                $notAccessibleEntities[$entityClass] = true;
            }
        }
        $notAccessibleEntities = array_keys($notAccessibleEntities);
        sort($notAccessibleEntities);

        foreach ($notAccessibleEntities as $entityClass) {
            $output->writeln(sprintf('<info>%s</info>', $entityClass));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function dumpResources(InputInterface $input, OutputInterface $output): void
    {
        $requestType = $this->getRequestType($input);
        // API version is not supported for now
        $version = Version::normalizeVersion(null);
        $entityClass = $this->resolveEntityClass($input->getArgument('entity'), $version, $requestType);
        $isSubresourcesRequested = $input->getOption('sub-resources');
        $actions = $input->getOption('action');
        $upsert = $input->getOption('upsert');

        $resources = $this->resourcesProvider->getResources($version, $requestType);
        /** @var ApiResource[] $sortedResources */
        $sortedResources = [];
        foreach ($resources as $resource) {
            $sortedResources[$resource->getEntityClass()] = $resource;
        }
        ksort($sortedResources);

        foreach ($sortedResources as $resource) {
            if ($entityClass && $resource->getEntityClass() !== $entityClass) {
                continue;
            }
            if ($actions && !$this->isResourceHasAnyOfActions($resource, $actions)) {
                continue;
            }
            $upsertAttribute = null;
            if ($upsert) {
                $upsertAttribute = $this->getUpsertAttribute($resource, $version, $requestType);
                if (!$upsertAttribute) {
                    continue;
                }
            }
            $output->writeln(sprintf('<info>%s</info>', $resource->getEntityClass()));
            $attributes = $this->getResourceAttributes($resource, $requestType);
            if ($upsertAttribute) {
                $attributes['Upsert Allowed By'] = $upsertAttribute;
            }
            $output->writeln($this->convertResourceAttributesToString($attributes));
            if ($isSubresourcesRequested) {
                $subresourcesText = $this->getEntitySubresourcesText(
                    $this->subresourcesProvider->getSubresources($resource->getEntityClass(), $version, $requestType),
                    $requestType
                );
                if ($subresourcesText) {
                    $output->writeln($subresourcesText);
                }
            }
        }
    }

    protected function getEntitySubresourcesText(
        ApiResourceSubresources $entitySubresources,
        RequestType $requestType
    ): string {
        $result = '';
        $subresources = $entitySubresources->getSubresources();
        if (!empty($subresources)) {
            $result .= ' Sub-resources:';
            foreach ($subresources as $associationName => $subresource) {
                $targetEntityType = $this->resolveEntityType($subresource->getTargetClassName(), $requestType);
                $acceptableTargetEntityTypes = [];
                foreach ($subresource->getAcceptableTargetClassNames() as $className) {
                    $acceptableTargetEntityTypes[] = $this->resolveEntityType($className, $requestType);
                }
                $result .= sprintf("\n  <comment>%s</comment>", $associationName);
                $result .= "\n   Type: " . ConfigUtil::getAssociationTargetType($subresource->isCollection());
                $result .= "\n   Target: " . $targetEntityType;
                if ($acceptableTargetEntityTypes) {
                    $result .= "\n   Acceptable Targets: " . implode(', ', $acceptableTargetEntityTypes);
                }
                $subresourceExcludedActions = $subresource->getExcludedActions();
                if (!empty($subresourceExcludedActions)) {
                    $result .= "\n   Excluded Actions: " . implode(', ', $subresourceExcludedActions);
                }
            }
        }

        return $result;
    }

    protected function getResourceAttributes(ApiResource $resource, RequestType $requestType): array
    {
        $result = [];

        $entityType = ValueNormalizerUtil::tryConvertToEntityType(
            $this->valueNormalizer,
            $resource->getEntityClass(),
            $requestType
        );
        $result['Entity Type'] = $entityType ?? '';

        $excludedActions = $resource->getExcludedActions();
        if (!empty($excludedActions)) {
            $result['Excluded Actions'] = implode(', ', $excludedActions);
        }

        return $result;
    }

    protected function convertResourceAttributesToString(array $attributes): string
    {
        $result = '';

        $i = 0;
        foreach ($attributes as $name => $value) {
            if ($i > 0) {
                $result .= PHP_EOL;
            }
            $result .= sprintf(' %s: %s', $name, $this->convertValueToString($value));
            $i++;
        }

        return $result;
    }

    protected function resolveEntityType(?string $entityClass, RequestType $requestType): ?string
    {
        if (!$entityClass) {
            return null;
        }

        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType
        );
    }

    private function isResourceHasAnyOfActions(ApiResource $resource, array $actions): bool
    {
        foreach ($actions as $action) {
            if (!$resource->isExcludedAction($action)) {
                return true;
            }
        }

        return false;
    }

    private function getUpsertAttribute(ApiResource $resource, string $version, RequestType $requestType): ?string
    {
        if ($resource->isExcludedAction(ApiAction::CREATE) && $resource->isExcludedAction(ApiAction::UPDATE)) {
            return null;
        }

        $config = $this->configProvider->getConfig(
            $resource->getEntityClass(),
            $version,
            $requestType,
            [new EntityDefinitionConfigExtra()]
        )->getDefinition();
        if (null === $config) {
            return null;
        }

        $upsertConfig = $config->getUpsertConfig();
        if (!$upsertConfig->isEnabled()) {
            return null;
        }

        $upsertAttribute = null;
        if ($upsertConfig->isAllowedById()) {
            $upsertAttribute .= $this->convertValueToString(['id']);
        }
        if ($upsertConfig->getFields()) {
            foreach ($upsertConfig->getFields() as $fieldNames) {
                if ($upsertAttribute) {
                    $upsertAttribute .= ', ';
                }
                $upsertAttribute .= $this->convertValueToString($fieldNames);
            }
        }

        return $upsertAttribute;
    }
}
