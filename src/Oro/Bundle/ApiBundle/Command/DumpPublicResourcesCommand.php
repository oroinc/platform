<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Request\PublicResource;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Provider\PublicResourcesLoader;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;

class DumpPublicResourcesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:resources:dump')
            ->setDescription('Dumps all public API resources.')
            // @todo: API version is not supported for now
            //->addArgument(
            //    'version',
            //    InputArgument::OPTIONAL,
            //    'API version',
            //    Version::LATEST
            //)
            ->addOption(
                'request-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'API request type',
                [RequestType::REST, RequestType::JSON_API]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $requestType = $input->getOption('request-type');
        // @todo: API version is not supported for now
        //$version     = $input->getArgument('version');
        $version = Version::LATEST;

        /** @var PublicResourcesLoader $resourcesLoader */
        $resourcesLoader = $this->getContainer()->get('oro_api.public_resources_loader');
        $resources       = $resourcesLoader->getResources($version, $requestType);

        $table = new Table($output);
        $table->setHeaders(['Entity', 'Attributes']);

        $i = 0;
        foreach ($resources as $resource) {
            if ($i > 0) {
                $table->addRow(new TableSeparator());
            }
            $table->addRow(
                [
                    $resource->getEntityClass(),
                    $this->convertResourceAttributesToString($this->getResourceAttributes($resource))
                ]
            );
            $i++;
        }

        $table->render();
    }

    /**
     * @param PublicResource $resource
     *
     * @return array
     */
    protected function getResourceAttributes(PublicResource $resource)
    {
        $result = [];

        $entityClass = $resource->getEntityClass();

        /** @var EntityAliasResolver $entityAliasResolver */
        $entityAliasResolver = $this->getContainer()->get('oro_entity.entity_alias_resolver');
        $result['Alias']     = $entityAliasResolver->getPluralAlias($entityClass);

        /** @var EntityClassNameProviderInterface $entityClassNameProvider */
        $entityClassNameProvider = $this->getContainer()->get('oro_entity.entity_class_name_provider');
        $result['Name']          = $entityClassNameProvider->getEntityClassName($entityClass);
        $result['Plural Name']   = $entityClassNameProvider->getEntityClassPluralName($entityClass);

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
