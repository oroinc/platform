<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CLI command to show resources accessible through API.
 */
class DumpCommand extends AbstractDebugCommand
{
    /** @var string */
    protected static $defaultName = 'oro:api:dump';

    /** @var SubresourcesProvider */
    private $subresourcesProvider;

    /** @var EntityClassProviderInterface */
    private $entityClassProvider;

    /**
     * @param ValueNormalizer              $valueNormalizer
     * @param ResourcesProvider            $resourcesProvider
     * @param SubresourcesProvider         $subresourcesProvider
     * @param EntityClassProviderInterface $entityClassProvider
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        EntityClassProviderInterface $entityClassProvider
    ) {
        parent::__construct($valueNormalizer, $resourcesProvider);
        $this->subresourcesProvider = $subresourcesProvider;
        $this->entityClassProvider = $entityClassProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dumps all resources accessible through API.')
            ->addArgument(
                'entity',
                InputArgument::OPTIONAL,
                'The entity class or entity type'
            )
            ->addOption(
                'sub-resources',
                null,
                InputOption::VALUE_NONE,
                'Shows sub-resources'
            )
            ->addOption(
                'not-accessible',
                null,
                InputOption::VALUE_NONE,
                'Shows only entities that are not accessible through API'
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $isNotAccessible = $input->getOption('not-accessible');
        if ($isNotAccessible) {
            $this->dumpNotAccessibleEntities($input, $output);
        } else {
            $this->dumpResources($input, $output);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function dumpNotAccessibleEntities(InputInterface $input, OutputInterface $output)
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function dumpResources(InputInterface $input, OutputInterface $output)
    {
        $requestType = $this->getRequestType($input);
        // API version is not supported for now
        $version = Version::normalizeVersion(null);
        $entityClass = $this->resolveEntityClass($input->getArgument('entity'), $version, $requestType);
        $isSubresourcesRequested = $input->getOption('sub-resources');

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
            $output->writeln(sprintf('<info>%s</info>', $resource->getEntityClass()));
            $output->writeln(
                $this->convertResourceAttributesToString(
                    $this->getResourceAttributes($resource, $requestType)
                )
            );
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

    /**
     * @param ApiResourceSubresources $entitySubresources
     * @param RequestType             $requestType
     *
     * @return string
     */
    protected function getEntitySubresourcesText(
        ApiResourceSubresources $entitySubresources,
        $requestType
    ) {
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

    /**
     * @param ApiResource $resource
     * @param RequestType $requestType
     *
     * @return array
     */
    protected function getResourceAttributes(ApiResource $resource, RequestType $requestType)
    {
        $result = [];

        $entityClass = $resource->getEntityClass();

        $result['Entity Type'] = $this->valueNormalizer->normalizeValue(
            $entityClass,
            DataType::ENTITY_TYPE,
            $requestType
        );

        $excludedActions = $resource->getExcludedActions();
        if (!empty($excludedActions)) {
            $result['Excluded Actions'] = implode(', ', $excludedActions);
        }

        return $result;
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    protected function convertResourceAttributesToString(array $attributes)
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

    /**
     * @param string|null $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function resolveEntityType($entityClass, RequestType $requestType)
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
}
