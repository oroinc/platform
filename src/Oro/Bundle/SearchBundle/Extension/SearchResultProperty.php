<?php

namespace Oro\Bundle\SearchBundle\Extension;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\TwigTemplateProperty;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchResultProperty extends TwigTemplateProperty
{
    /**  @var SearchMappingProvider */
    protected $mappingProvider;

    /**
     * @var array
     * @deprecated since 1.8 Please use mappingProvider for mapping config
     */
    protected $entitiesConfig;

    public function __construct(\Twig_Environment $environment, $config)
    {
        parent::__construct($environment);

        $this->entitiesConfig = $config;
    }

    /**
     * @param SearchMappingProvider $mappingProvider
     */
    public function setMappingProvider(SearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(ResultRecordInterface $record)
    {
        $entity = $record->getValue('entity');
        $entityClass = ClassUtils::getRealClass($entity);

        if (!$this->mappingProvider->isClassSupported($entityClass)) {
            throw new InvalidConfigurationException(
                sprintf('Unknown entity type %s, unable to find search configuration', $entityClass)
            );
        } else {
            $searchTemplate = $this->mappingProvider->getMappingConfig()[$entityClass]['search_template'];
        }

        if (!$this->params->offsetGetOr('template', false)) {
            $this->params->offsetSet('template', $searchTemplate);
        }

        return $this->getTemplate()->render(
            [
                'indexer_item' => $record->getValue('indexer_item'),
                'entity'       => $record->getValue('entity'),
            ]
        );
    }

    /**
     * @param array $configArray
     */
    public function setEntitiesConfig(array $configArray)
    {
        $this->entitiesConfig = $configArray;
    }
}
