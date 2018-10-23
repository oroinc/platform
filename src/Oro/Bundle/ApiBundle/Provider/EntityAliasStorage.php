<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage as BaseEntityAliasStorage;

/**
 * The storage for Data API entity aliases.
 */
class EntityAliasStorage extends BaseEntityAliasStorage
{
    /** @var string[] */
    private $configFiles;

    /**
     * @param string[] $configFiles
     */
    public function __construct(array $configFiles)
    {
        $this->configFiles = $configFiles;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDuplicateAliasHelpMessage()
    {
        if (empty($this->configFiles)) {
            return parent::getDuplicateAliasHelpMessage();
        }

        $configFiles = array_map(
            function ($fileName) {
                return sprintf('"Resources/config/oro/%s"', $fileName);
            },
            $this->configFiles
        );
        $lastConfigFile = array_pop($configFiles);
        $listOfConfigFiles = empty($configFiles)
            ? $lastConfigFile
            : implode(', ', $configFiles) . ' or ' . $lastConfigFile;

        return
            'To solve this problem you can use "entity_aliases" section in '
            . $listOfConfigFiles
            . ', use "entity_aliases" or "entity_alias_exclusions" section in "Resources/config/oro/entity.yml" '
            . 'or create a service to provide aliases for conflicting classes '
            . 'and register it with "oro_entity.alias_provider" tag in DI container.';
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAlias($entityClass, $value, $isPluralAlias)
    {
        if (!preg_match('/^[a-z][a-z0-9-_]*$/D', $value)) {
            throw new InvalidEntityAliasException(sprintf(
                'The string "%s" cannot be used as %s for the "%s" entity '
                . 'because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'lower case letters, numbers, hyphens ("-") and underscores ("_").',
                $value,
                $isPluralAlias ? 'the plural alias' : 'the alias',
                $entityClass
            ));
        }
    }
}
