<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class InlineEditingExtension extends AbstractExtension
{
    /**
     * @var OroEntityManager
     */
    protected $entityManager;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param OroEntityManager $entityManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(OroEntityManager $entityManager, SecurityFacade $securityFacade)
    {
        $this->entityManager = $entityManager;
        $this->securityFacade = $securityFacade;
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
        $isGranted = $this->securityFacade->isGranted('EDIT', 'entity:' . $configItems['entity_name']);

        $normalizedConfigItems = $this->validateConfiguration(
            $configuration,
            [Configuration::BASE_CONFIG_KEY => $configItems]
        );

        if (!$isGranted) {
            $normalizedConfigItems[Configuration::CONFIG_KEY_ENABLE] = false;
        }

        // replace config values by normalized, extra keys passed directly
        $config->offsetSet(
            Configuration::BASE_CONFIG_KEY,
            array_replace_recursive($configItems, $normalizedConfigItems)
        );

        //add inline editing where it is possible
        if ($isGranted) {
            $columns = $config->offsetGetOr(FormatterConfiguration::COLUMNS_KEY, []);
            $metadata = $this->entityManager->getClassMetadata($configItems['entity_name']);
            $blackList = $configuration->getBlackList();

            foreach ($columns as $columnName => &$column) {
                if ($metadata->hasField($columnName)
                    && !in_array($columnName, $blackList)
                    && !$metadata->hasAssociation($columnName)
                ) {
                    $column[Configuration::BASE_CONFIG_KEY] = ['enable' => true];
                } elseif ($metadata->hasAssociation($columnName)) {
                    $mapping = $metadata->getAssociationMapping($columnName);
                    if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                        //try to create select list
                    }
                }
            }

            $config->offsetSet(FormatterConfiguration::COLUMNS_KEY, $columns);
        }
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
}
