<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Oro\Bundle\EntityBundle\Helper\DictionaryHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

class InlineEditingExtension extends AbstractExtension
{
    /**
     * @var OroEntityManager
     */
    protected $entityManager;

    /**
     * @var DictionaryHelper
     */
    protected $dictionaryHelper;

    public function __construct(OroEntityManager $entityManager, DictionaryHelper $dictionaryHelper)
    {
        $this->entityManager = $entityManager;
        $this->dictionaryHelper = $dictionaryHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(Configuration::ENABLED_CONFIG_PATH);
    }

    /**
     * Validate configs nad fill default values
     *
     * @param DatagridConfiguration $config
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $configItems    = $config->offsetGetOr(Configuration::BASE_CONFIG_KEY, []);
        $configuration   = new Configuration(Configuration::BASE_CONFIG_KEY);

        $normalizedConfigItems = $this->validateConfiguration(
            $configuration,
            [Configuration::BASE_CONFIG_KEY => $configItems]
        );

        // replace config values by normalized, extra keys passed directly
        $config->offsetSet(
            Configuration::BASE_CONFIG_KEY,
            array_replace_recursive($configItems, $normalizedConfigItems)
        );

        //add inline editing where it is possible
        $columns = $config->offsetGetOr(FormatterConfiguration::COLUMNS_KEY, []);
        $configParams = $config->offsetGet(Configuration::BASE_CONFIG_KEY);
        $metadata = $this->entityManager->getClassMetadata($configParams['entity_name']);

        foreach ($columns as $columnName => &$column) {
            if ($metadata->hasField($columnName)) {
                $column[Configuration::BASE_CONFIG_KEY] = ['enable' => true];
            } elseif ($metadata->hasAssociation($columnName)) {
                //create select list if possible
            }
        }

        $config->offsetSet(FormatterConfiguration::COLUMNS_KEY, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetSet(
            Configuration::BASE_CONFIG_KEY,
            $config->offsetGetOr(Configuration::BASE_CONFIG_KEY, [])
        );
    }

    protected function prepareChoices($results, $keyField, $labelField)
    {
        $resultsData = [];
        $methodGetPK = 'get' . ucfirst($keyField);
        $methodGetLabel = 'get' . ucfirst($labelField);
        foreach ($results as $result) {
            $resultsData[$result->$methodGetPK()] = $result->$methodGetLabel();
        }

        return $resultsData;
    }
}
