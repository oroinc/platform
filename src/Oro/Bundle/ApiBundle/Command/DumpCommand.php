<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Oro\Bundle\ApiBundle\Provider\ResourcesLoader;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

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
                'The entity class name or alias'
            );
           // @todo: API version is not supported for now
            //->addArgument(
            //    'version',
            //    InputArgument::OPTIONAL,
            //    'API version',
            //    Version::LATEST
            //);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $entityClass = $input->getArgument('entity');
        if ($entityClass) {
            /** @var EntityClassNameHelper $entityClassNameHelper */
            $entityClassNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');
            $entityClass = $entityClassNameHelper->resolveEntityClass($entityClass, true);
        }
        $requestType = $this->getRequestType($input);
        // @todo: API version is not supported for now
        //$version     = $input->getArgument('version');
        $version = Version::LATEST;

        /** @var ResourcesLoader $resourcesLoader */
        $resourcesLoader = $this->getContainer()->get('oro_api.resources_loader');
        $resources       = $resourcesLoader->getResources($version, $requestType);

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
            $table->addRow(
                [
                    $resource->getEntityClass(),
                    $this->convertResourceAttributesToString($this->getResourceAttributes($resource, $requestType))
                ]
            );
            $i++;
        }

        $table->render();
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
        $valueNormalizer      = $this->getContainer()->get('oro_api.value_normalizer');
        $result['Entity Type'] = $valueNormalizer->normalizeValue(
            $entityClass,
            DataType::ENTITY_TYPE,
            $requestType
        );

        /** @var EntityClassNameProviderInterface $entityClassNameProvider */
        $entityClassNameProvider = $this->getContainer()->get('oro_entity.entity_class_name_provider');
        $result['Name']          = $entityClassNameProvider->getEntityClassName($entityClass);
        $result['Plural Name']   = $entityClassNameProvider->getEntityClassPluralName($entityClass);

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
            $result .= sprintf('%s: %s', $name, $value);
            $i++;
        }

        return $result;
    }
}
