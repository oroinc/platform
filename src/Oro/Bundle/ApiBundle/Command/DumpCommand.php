<?php

namespace Oro\Bundle\ApiBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CLI command to show resources accessible through Data API.
 */
class DumpCommand extends AbstractDebugCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:dump')
            ->setDescription('Dumps all resources accessible through Data API.')
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
                'Shows only entities that are not accessible through Data API'
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

        /** @var ResourcesProvider $resourcesProvider */
        $resourcesProvider = $this->getContainer()->get('oro_api.resources_provider');
        $resources = $resourcesProvider->getResources($version, $requestType);
        $accessibleEntities = [];
        foreach ($resources as $resource) {
            $accessibleEntities[$resource->getEntityClass()] = true;
        }

        $notAccessibleEntities = [];
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $managers = $doctrine->getManagers();
        foreach ($managers as $manager) {
            if (!$manager instanceof EntityManager) {
                continue;
            }
            /** @var ClassMetadata[] $allMetadata */
            $allMetadata = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($allMetadata as $metadata) {
                if (!isset($accessibleEntities[$metadata->name])
                    && !isset($notAccessibleEntities[$metadata->name])
                    && !$metadata->isMappedSuperclass
                ) {
                    $notAccessibleEntities[$metadata->name] = true;
                }
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

        /** @var ResourcesProvider $resourcesProvider */
        $resourcesProvider = $this->getContainer()->get('oro_api.resources_provider');
        $resources = $resourcesProvider->getResources($version, $requestType);
        /** @var ApiResource[] $sortedResources */
        $sortedResources = [];
        foreach ($resources as $resource) {
            $sortedResources[$resource->getEntityClass()] = $resource;
        }
        ksort($sortedResources);

        /** @var SubresourcesProvider $subresourcesProvider */
        $subresourcesProvider = $this->getContainer()->get('oro_api.subresources_provider');

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
                    $subresourcesProvider->getSubresources($resource->getEntityClass(), $version, $requestType),
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
                $result .= "\n   Type: " . ($subresource->isCollection() ? 'to-many' : 'to-one');
                $result .= "\n   Target: " . $targetEntityType;
                $result .= "\n   Acceptable Targets: " . implode(', ', $acceptableTargetEntityTypes);
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

        /** @var ValueNormalizer $valueNormalizer */
        $valueNormalizer = $this->getContainer()->get('oro_api.value_normalizer');
        $result['Entity Type'] = $valueNormalizer->normalizeValue(
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
            $this->getContainer()->get('oro_api.value_normalizer'),
            $entityClass,
            $requestType
        );
    }
}
