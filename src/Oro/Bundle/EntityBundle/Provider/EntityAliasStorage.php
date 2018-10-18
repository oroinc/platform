<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Exception\DuplicateEntityAliasException;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;

/**
 * The storage for entity aliases.
 */
class EntityAliasStorage implements \Serializable
{
    /** @var EntityAlias[] */
    private $aliases = [];

    /** @var array */
    private $aliasToClass = [];

    /** @var array */
    private $pluralAliasToClass = [];

    /**
     * Adds an alias for the given entity.
     *
     * @param string      $entityClass
     * @param EntityAlias $entityAlias
     *
     * @throws InvalidEntityAliasException if the given entity alias has invalid alias or plural alias
     * @throws DuplicateEntityAliasException if duplicated entity alias is detected
     */
    public function addEntityAlias($entityClass, EntityAlias $entityAlias)
    {
        $this->validateAliasNotBlank($entityClass, $entityAlias->getAlias(), false);
        $this->validateAliasNotBlank($entityClass, $entityAlias->getPluralAlias(), true);
        $this->validateAlias($entityClass, $entityAlias->getAlias(), false);
        $this->validateAlias($entityClass, $entityAlias->getPluralAlias(), true);
        $this->validateDuplicates($entityClass, $entityAlias);
        $this->aliases[$entityClass] = $entityAlias;
        $this->aliasToClass[$entityAlias->getAlias()] = $entityClass;
        $this->pluralAliasToClass[$entityAlias->getPluralAlias()] = $entityClass;
    }

    /**
     * Returns all entity aliases.
     *
     * @return EntityAlias[] [entity class => EntityAlias, ...]
     */
    public function getAll()
    {
        return $this->aliases;
    }

    /**
     * Removes all data from the storage.
     */
    public function clear()
    {
        $this->aliases = [];
        $this->aliasToClass = [];
        $this->pluralAliasToClass = [];
    }

    /**
     * Returns an alias for the given entity.
     *
     * @param string $entityClass
     *
     * @return EntityAlias|null
     */
    public function getEntityAlias($entityClass)
    {
        return $this->aliases[$entityClass] ?? null;
    }

    /**
     * Returns entity class name associated with the given alias.
     *
     * @param string $alias
     *
     * @return string|null
     */
    public function getClassByAlias($alias)
    {
        return $this->aliasToClass[$alias] ?? null;
    }

    /**
     * Returns entity class name associated with the given plural alias.
     *
     * @param string $pluralAlias
     *
     * @return string|null
     */
    public function getClassByPluralAlias($pluralAlias)
    {
        return $this->pluralAliasToClass[$pluralAlias] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $data = [];
        foreach ($this->aliases as $entityClass => $entityAlias) {
            $data[$entityClass] = [$entityAlias->getAlias(), $entityAlias->getPluralAlias()];
        }

        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->aliases = [];
        $this->aliasToClass = [];
        $this->pluralAliasToClass = [];

        $data = unserialize($serialized);
        foreach ($data as $entityClass => $item) {
            $this->aliases[$entityClass] = new EntityAlias($item[0], $item[1]);
            $this->aliasToClass[$item[0]] = $entityClass;
            $this->pluralAliasToClass[$item[1]] = $entityClass;
        }
    }

    /**
     * Returns a message which should be added to the "duplicate alias" exception
     * to help a developer to resolve the conflict.
     *
     * @return string
     */
    protected function getDuplicateAliasHelpMessage()
    {
        return
            'To solve this problem you can '
            . 'use "entity_aliases" or "entity_alias_exclusions" section in "Resources/config/oro/entity.yml" '
            . 'or create a service to provide aliases for conflicting classes '
            . 'and register it with "oro_entity.alias_provider" tag in DI container.';
    }

    /**
     * Validates that the given value is not empty string and not NULL.
     *
     * @param string      $entityClass
     * @param string|null $value
     * @param bool        $isPluralAlias
     *
     * @throws InvalidEntityAliasException if the given value is empty string or NULL
     */
    protected function validateAliasNotBlank($entityClass, $value, $isPluralAlias)
    {
        if (!$value) {
            throw new InvalidEntityAliasException(sprintf(
                '%s for the "%s" entity must not be empty.',
                $isPluralAlias ? 'The plural alias' : 'The alias',
                $entityClass
            ));
        }
    }

    /**
     * Validates that the given value can be used as an entity alias.
     *
     * @param string      $entityClass
     * @param string|null $value
     * @param bool        $isPluralAlias
     *
     * @throws InvalidEntityAliasException if the given value cannot be used as an entity alias
     */
    protected function validateAlias($entityClass, $value, $isPluralAlias)
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $value)) {
            throw new InvalidEntityAliasException(sprintf(
                'The string "%s" cannot be used as %s for the "%s" entity '
                . 'because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'lower case letters, numbers and underscores ("_").',
                $value,
                $isPluralAlias ? 'the plural alias' : 'the alias',
                $entityClass
            ));
        }
    }

    /**
     * @param string      $entityClass
     * @param EntityAlias $entityAlias
     *
     * @throws DuplicateEntityAliasException if duplicated entity alias is detected
     */
    protected function validateDuplicates($entityClass, EntityAlias $entityAlias)
    {
        if (isset($this->aliasToClass[$entityAlias->getAlias()])) {
            throw new DuplicateEntityAliasException(sprintf(
                'The alias "%s" cannot be used for the entity "%s" '
                . 'because it is already used for the entity "%s". '
                . $this->getDuplicateAliasHelpMessage(),
                $entityAlias->getAlias(),
                $entityClass,
                $this->aliasToClass[$entityAlias->getAlias()]
            ));
        }
        if (isset($this->pluralAliasToClass[$entityAlias->getPluralAlias()])) {
            throw new DuplicateEntityAliasException(sprintf(
                'The plural alias "%s" cannot be used for the entity "%s" '
                . 'because it is already used for the entity "%s". '
                . $this->getDuplicateAliasHelpMessage(),
                $entityAlias->getPluralAlias(),
                $entityClass,
                $this->pluralAliasToClass[$entityAlias->getPluralAlias()]
            ));
        }
        if (isset($this->pluralAliasToClass[$entityAlias->getAlias()])) {
            throw new DuplicateEntityAliasException(sprintf(
                'The alias "%s" cannot be used for the entity "%s" '
                . 'because it is already used as a plural alias for the entity "%s". '
                . $this->getDuplicateAliasHelpMessage(),
                $entityAlias->getAlias(),
                $entityClass,
                $this->pluralAliasToClass[$entityAlias->getAlias()]
            ));
        }
        if (isset($this->aliasToClass[$entityAlias->getPluralAlias()])) {
            throw new DuplicateEntityAliasException(sprintf(
                'The plural alias "%s" cannot be used for the entity "%s" '
                . 'because it is already used as an alias for the entity "%s". '
                . $this->getDuplicateAliasHelpMessage(),
                $entityAlias->getPluralAlias(),
                $entityClass,
                $this->aliasToClass[$entityAlias->getPluralAlias()]
            ));
        }
    }
}
