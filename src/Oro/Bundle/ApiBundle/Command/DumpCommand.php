<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;

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
            // @todo: API version is not supported for now
            //->addArgument(
            //    'version',
            //    InputArgument::OPTIONAL,
            //    'API version',
            //    Version::LATEST
            //)
            ->addOption(
                'sub-resources',
                null,
                InputOption::VALUE_NONE,
                'Shows sub resources'
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
        $version = Version::normalizeVersion(null);
        $isSubresourcesRequested = $input->getOption('sub-resources');

        /** @var ResourcesProvider $resourcesProvider */
        $resourcesProvider = $this->getContainer()->get('oro_api.resources_provider');
        $resources = $resourcesProvider->getResources($version, $requestType);

        /** @var SubresourcesProvider $subresourcesProvider */
        $subresourcesProvider = $this->getContainer()->get('oro_api.subresources_provider');

        $table = new Table($output);
        $table->setHeaders(['Entity', 'Attributes']);

        $i = 0;
        foreach ($resources as $resource) {
            if ($entityClass && $resource->getEntityClass() !== $entityClass) {
                continue;
            }
            if ($i > 0) {
                $table->addRow(new TableSeparator());
            }
            $entityCellText = $resource->getEntityClass();
            if ($isSubresourcesRequested) {
                $entitySubresourcesText = $this->getEntitySubresourcesText(
                    $subresourcesProvider->getSubresources($resource->getEntityClass(), $version, $requestType)
                );
                if ($entitySubresourcesText) {
                    $entityCellText .= "\n" . $entitySubresourcesText;
                }
            }
            $table->addRow(
                [
                    $entityCellText,
                    $this->convertResourceAttributesToString(
                        $this->getResourceAttributes($resource, $version, $requestType)
                    )
                ]
            );
            $i++;
        }

        $table->render();
    }

    /**
     * @param ApiResourceSubresources $entitySubresources
     *
     * @return string
     */
    protected function getEntitySubresourcesText(ApiResourceSubresources $entitySubresources)
    {
        $result = '';
        $subresources = $entitySubresources->getSubresources();
        if (!empty($subresources)) {
            $result .= 'Sub resources:';
            foreach ($subresources as $associationName => $subresource) {
                $result .= "\n  " . $associationName;
                $subresourceExcludedActions = $subresource->getExcludedActions();
                if (!empty($subresourceExcludedActions)) {
                    $result .= "\n    Excluded Actions: " . implode(', ', $subresourceExcludedActions);
                }
            }
        }

        return $result;
    }

    /**
     * @param ApiResource $resource
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array
     */
    protected function getResourceAttributes(ApiResource $resource, $version, RequestType $requestType)
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

        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.config_provider');
        $config = $configProvider->getConfig(
            $entityClass,
            $version,
            $requestType,
            [
                new EntityDefinitionConfigExtra(),
                new FilterIdentifierFieldsConfigExtra(),
                new DescriptionsConfigExtra()
            ]
        );
        $entityDefinition = $config->getDefinition();
        if ($entityDefinition) {
            $result['Name'] = $entityDefinition->getLabel();
            $result['Plural Name'] = $entityDefinition->getPluralLabel();
        }

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
            $result .= sprintf('%s: %s', $name, $this->convertValueToString($value));
            $i++;
        }

        return $result;
    }
}
