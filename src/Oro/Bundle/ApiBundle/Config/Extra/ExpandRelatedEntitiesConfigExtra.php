<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * An instance of this class can be added to the config extras of the context
 * to request to add related entities to a result.
 */
class ExpandRelatedEntitiesConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'expand_related_entities';

    /** @var string[] */
    private array $expandedEntities;
    /** @var array|null */
    private ?array $map = null;

    /**
     * @param string[] $expandedEntities The list of related entities.
     *                                   Each item can be an association name or a path to a nested association.
     *                                   Example: ["comments", "comments.author"]
     *                                   Where "comments" is an association under a primary entity,
     *                                   "author" is an association under the "comments" entity.
     */
    public function __construct(array $expandedEntities)
    {
        $this->expandedEntities = $expandedEntities;
    }

    /**
     * Gets the list of related entities.
     * Each item can be an association name or a path to a nested association.
     * Example: ["comments", "comments.author"]
     * Where "comments" is an association under a primary entity,
     * "author" is an association under the "comments" entity.
     *
     * @return string[]
     */
    public function getExpandedEntities(): array
    {
        return $this->expandedEntities;
    }

    /**
     * Checks if it is requested to expand an entity by the given path.
     */
    public function isExpandRequested(string $path): bool
    {
        if (null === $this->map) {
            $this->map = $this->buildMap();
        }

        return isset($this->map[$path]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context): void
    {
        $context->set(self::NAME, $this->expandedEntities);
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return 'expand:' . implode(',', $this->expandedEntities);
    }

    private function buildMap(): array
    {
        $map = [];
        foreach ($this->expandedEntities as $path) {
            do {
                if (isset($map[$path])) {
                    break;
                }
                $map[$path] = true;
                $lastDelimiter = strrpos($path, ConfigUtil::PATH_DELIMITER);
                $path = false !== $lastDelimiter
                    ? substr($path, 0, $lastDelimiter)
                    : null;
            } while ($path);
        }

        return $map;
    }
}
