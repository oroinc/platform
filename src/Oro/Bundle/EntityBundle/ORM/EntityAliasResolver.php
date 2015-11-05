<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Exception\RuntimeException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

class EntityAliasResolver implements WarmableInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var bool */
    protected $debug;

    /** @var EntityAliasProviderInterface[] */
    private $providers = [];

    /** @var EntityAlias[] */
    private $aliases = [];

    /** @var array */
    private $aliasToClass = [];

    /** @var array */
    private $pluralAliasToClass = [];

    /** @var array */
    private $unknown = [];

    /** @var string */
    private $duplicateAliasHelpMessage;

    /** @var bool */
    private $allAliasesLoaded = false;

    /**
     * @param ManagerRegistry $doctrine
     * @param bool            $debug
     */
    public function __construct(ManagerRegistry $doctrine, $debug)
    {
        $this->doctrine = $doctrine;
        $this->debug    = $debug;
    }

    /**
     * Returns the alias for the given entity class.
     *
     * @param string $entityClass The FQCN of an entity
     *
     * @return string The alias for the requested entity
     *
     * @throws EntityAliasNotFoundException if an alias not found
     * @throws RuntimeException if duplicate alias is found
     */
    public function getAlias($entityClass)
    {
        $entityAlias = $this->findEntityAlias($entityClass);
        if (!$entityAlias) {
            throw new EntityAliasNotFoundException(
                sprintf('An alias for "%s" entity not found.', $entityClass)
            );
        }

        return $entityAlias->getAlias();
    }

    /**
     * Returns the plural alias for the given entity class.
     *
     * @param string $entityClass The FQCN of an entity
     *
     * @return string The plural alias for the requested entity
     *
     * @throws EntityAliasNotFoundException if an alias not found
     * @throws RuntimeException if duplicate alias is found
     */
    public function getPluralAlias($entityClass)
    {
        $entityAlias = $this->findEntityAlias($entityClass);
        if (!$entityAlias) {
            throw new EntityAliasNotFoundException(
                sprintf('A plural alias for "%s" entity not found.', $entityClass)
            );
        }

        return $entityAlias->getPluralAlias();
    }

    /**
     * Returns the entity class by the given alias.
     *
     * @param string $alias The alias of an entity
     *
     * @return string The FQCN of an entity
     *
     * @throws EntityAliasNotFoundException if the given alias is not associated with any entity class
     */
    public function getClassByAlias($alias)
    {
        $entityClass = $this->findClassByAlias($alias);
        if (!$entityClass) {
            throw new EntityAliasNotFoundException(
                sprintf('The alias "%s" is not associated with any entity class.', $alias)
            );
        }

        return $entityClass;
    }

    /**
     * Returns the entity class by the given plural alias.
     *
     * @param string $pluralAlias The plural alias of an entity
     *
     * @return string The FQCN of an entity
     *
     * @throws EntityAliasNotFoundException if the given plural alias is not associated with any entity class
     */
    public function getClassByPluralAlias($pluralAlias)
    {
        $entityClass = $this->findClassByPluralAlias($pluralAlias);
        if (!$entityClass) {
            throw new EntityAliasNotFoundException(
                sprintf('The plural alias "%s" is not associated with any entity class.', $pluralAlias)
            );
        }

        return $entityClass;
    }

    /**
     * Returns all entity aliases
     *
     * @return EntityAlias[]
     */
    public function getAll()
    {
        $this->ensureAllAliasesLoaded();

        return $this->aliases;
    }

    /**
     * Adds a provider for entity aliases to the provider list
     *
     * @param EntityAliasProviderInterface $provider
     */
    public function addProvider(EntityAliasProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        // just check that aliases for all entities can be loaded
        $this->ensureAllAliasesLoaded();
    }

    /**
     * Sets a message which is added to the "duplicate alias" exception
     * to help a developer to resolve the conflict
     *
     * @param string $message
     */
    public function setDuplicateAliasHelpMessage($message)
    {
        $this->duplicateAliasHelpMessage = $message;
    }

    /**
     * Returns a message which should be added to the "duplicate alias" exception
     * to help a developer to resolve the conflict
     *
     * @return string
     */
    protected function getDuplicateAliasHelpMessage()
    {
        return $this->duplicateAliasHelpMessage ?: 'To solve this problem '
            . 'you can use "entity_aliases" or "entity_alias_exclusions" section in the '
            . '"Resources/config/oro/entity.yml" of your bundle '
            . 'or create a service to provide aliases for conflicting classes '
            . 'and register it with the tag "oro_entity.alias_provider" in DI container.';
    }

    /**
     * @param string $entityClass
     *
     * @return EntityAlias|null
     *
     * @throws RuntimeException if duplicate alias is found
     */
    protected function findEntityAlias($entityClass)
    {
        if (isset($this->aliases[$entityClass])) {
            return $this->aliases[$entityClass];
        }
        if (isset($this->unknown['class'][$entityClass])) {
            return null;
        }

        $entityAlias = null;
        foreach ($this->providers as $provider) {
            $entityAlias = $provider->getEntityAlias($entityClass);
            if (null !== $entityAlias) {
                break;
            }
        }

        if ($entityAlias) {
            if ($this->validateDuplicates($entityClass, $entityAlias)) {
                $this->aliases[$entityClass] = $entityAlias;

                $this->aliasToClass[$entityAlias->getAlias()]             = $entityClass;
                $this->pluralAliasToClass[$entityAlias->getPluralAlias()] = $entityClass;
            }
        } else {
            $this->unknown['class'][$entityClass] = true;
        }

        return $entityAlias;
    }

    /**
     * @param string $alias
     *
     * @return string|null
     */
    protected function findClassByAlias($alias)
    {
        if (isset($this->aliasToClass[$alias])) {
            return $this->aliasToClass[$alias];
        }
        if (isset($this->unknown['alias'][$alias])) {
            return null;
        }

        $this->ensureAllAliasesLoaded();

        if (isset($this->aliasToClass[$alias])) {
            return $this->aliasToClass[$alias];
        }

        $this->unknown['alias'][$alias] = true;

        return null;
    }

    /**
     * @param string $pluralAlias
     *
     * @return string|null
     */
    protected function findClassByPluralAlias($pluralAlias)
    {
        if (isset($this->pluralAliasToClass[$pluralAlias])) {
            return $this->pluralAliasToClass[$pluralAlias];
        }
        if (isset($this->unknown['pluralAlias'][$pluralAlias])) {
            return null;
        }

        $this->ensureAllAliasesLoaded();

        if (isset($this->pluralAliasToClass[$pluralAlias])) {
            return $this->pluralAliasToClass[$pluralAlias];
        }

        $this->unknown['pluralAlias'][$pluralAlias] = true;

        return null;
    }

    /**
     * Makes sure that aliases for all entities are loaded
     */
    protected function ensureAllAliasesLoaded()
    {
        if ($this->allAliasesLoaded) {
            return;
        }

        /** @var ClassMetadata[] $allMetadata */
        $allMetadata = $this->doctrine->getManager()->getMetadataFactory()->getAllMetadata();
        foreach ($allMetadata as $metadata) {
            if (!$metadata->isMappedSuperclass && !isset($this->aliases[$metadata->name])) {
                $this->findEntityAlias($metadata->name);
            }
        }
        $this->allAliasesLoaded = true;
    }

    /**
     * @param string      $entityClass
     * @param EntityAlias $entityAlias
     *
     * @return bool TRUE if no duplicates detected; otherwise FALSE in not debugging environment
     *              or RuntimeException in debugging environment
     *
     * @throws RuntimeException if duplicate alias is found
     */
    protected function validateDuplicates($entityClass, EntityAlias $entityAlias)
    {
        if (isset($this->aliasToClass[$entityAlias->getAlias()])) {
            if ($this->debug) {
                throw new RuntimeException(
                    sprintf(
                        'The alias "%s" cannot be used for the entity "%s" '
                        . 'because it is already used for the entity "%s". '
                        . $this->getDuplicateAliasHelpMessage(),
                        $entityAlias->getAlias(),
                        $entityClass,
                        $this->aliasToClass[$entityAlias->getAlias()]
                    )
                );
            }

            return false;
        }
        if (isset($this->pluralAliasToClass[$entityAlias->getPluralAlias()])) {
            if ($this->debug) {
                throw new RuntimeException(
                    sprintf(
                        'The plural alias "%s" cannot be used for the entity "%s" '
                        . 'because it is already used for the entity "%s". '
                        . $this->getDuplicateAliasHelpMessage(),
                        $entityAlias->getPluralAlias(),
                        $entityClass,
                        $this->pluralAliasToClass[$entityAlias->getPluralAlias()]
                    )
                );
            }

            return false;
        }
        if (isset($this->pluralAliasToClass[$entityAlias->getAlias()])) {
            if ($this->debug) {
                throw new RuntimeException(
                    sprintf(
                        'The alias "%s" cannot be used for the entity "%s" '
                        . 'because it is already used as a plural alias for the entity "%s". '
                        . $this->getDuplicateAliasHelpMessage(),
                        $entityAlias->getAlias(),
                        $entityClass,
                        $this->pluralAliasToClass[$entityAlias->getAlias()]
                    )
                );
            }

            return false;
        }
        if (isset($this->aliasToClass[$entityAlias->getPluralAlias()])) {
            if ($this->debug) {
                throw new RuntimeException(
                    sprintf(
                        'The plural alias "%s" cannot be used for the entity "%s" '
                        . 'because it is already used as an alias for the entity "%s". '
                        . $this->getDuplicateAliasHelpMessage(),
                        $entityAlias->getPluralAlias(),
                        $entityClass,
                        $this->aliasToClass[$entityAlias->getPluralAlias()]
                    )
                );
            }

            return false;
        }

        return true;
    }
}
