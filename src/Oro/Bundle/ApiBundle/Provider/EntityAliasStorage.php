<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage as BaseEntityAliasStorage;

/**
 * The storage for API entity aliases.
 */
class EntityAliasStorage extends BaseEntityAliasStorage
{
    /** @var string[] */
    private array $configFiles;

    /**
     * @param string[] $configFiles
     */
    public function __construct(array $configFiles)
    {
        $this->configFiles = $configFiles;
    }

    #[\Override]
    protected function getDuplicateAliasHelpMessage()
    {
        if (empty($this->configFiles)) {
            return parent::getDuplicateAliasHelpMessage();
        }

        $configFiles = array_map(
            function ($fileName) {
                return \sprintf('"Resources/config/oro/%s"', $fileName);
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

    #[\Override]
    protected function validateAlias($entityClass, $value, $isPluralAlias)
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\-_]*$/D', $value)) {
            throw new InvalidEntityAliasException(\sprintf(
                'The string "%s" cannot be used as %s for the "%s" entity '
                . 'because it contains illegal characters. '
                . 'The valid alias should start with a latin letter and only contain '
                . 'latin letters, digits, underscores ("_") and hyphens ("-").',
                $value,
                $isPluralAlias ? 'the plural alias' : 'the alias',
                $entityClass
            ));
        }
    }
}
