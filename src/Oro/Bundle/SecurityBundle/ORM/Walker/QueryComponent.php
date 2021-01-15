<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Represents a additional info for aliased AST expression,
 * such as "entityAlias.fieldName AS fieldAlias" or "parentEntityAlias.associationName AS entityAlias".
 */
class QueryComponent
{
    /** @var ClassMetadata */
    private $metadata;

    /** @var array|null */
    private $relation;

    /** @var string|null */
    private $parent;

    /** @var string|null */
    private $map;

    /** @var int|null */
    private $nestingLevel;

    /** @var array|null */
    private $token;

    /**
     * @param ClassMetadata $metadata
     * @param array|null    $relation
     * @param string|null   $parent
     * @param string|null   $map
     * @param int|null      $nestingLevel
     * @param array|null    $token
     */
    public function __construct(
        ClassMetadata $metadata,
        array $relation = null,
        string $parent = null,
        string $map = null,
        int $nestingLevel = null,
        array $token = null
    ) {
        $this->metadata = $metadata;
        $this->relation = $relation;
        $this->parent = $parent;
        $this->map = $map;
        $this->nestingLevel = $nestingLevel;
        $this->token = $token;
    }

    /**
     * Generates and returns query component from array representation.
     *
     * @param array $componentArray
     *
     * @return QueryComponent|null
     */
    public static function fromArray(array $componentArray): ?QueryComponent
    {
        if (!array_key_exists('metadata', $componentArray)) {
            // the given query component is not the table data
            return null;
        }

        return new self(
            $componentArray['metadata'],
            $componentArray['relation'],
            $componentArray['parent'],
            $componentArray['map'],
            $componentArray['nestingLevel'],
            $componentArray['token']
        );
    }

    /**
     * @return ClassMetadata
     */
    public function getMetadata(): ClassMetadata
    {
        return $this->metadata;
    }

    /**
     * @return array|null
     */
    public function getRelation(): ?array
    {
        return $this->relation;
    }

    /**
     * @return null|string
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * @return string|null
     */
    public function getMap(): ?string
    {
        return $this->map;
    }

    /**
     * @return int|null
     */
    public function getNestingLevel(): ?int
    {
        return $this->nestingLevel;
    }

    /**
     * @return array|null
     */
    public function getToken(): ?array
    {
        return $this->token;
    }

    /**
     * Returns array representation of query component.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'metadata'     => $this->getMetadata(),
            'relation'     => $this->getRelation(),
            'parent'       => $this->getParent(),
            'map'          => $this->getMap(),
            'nestingLevel' => $this->getNestingLevel(),
            'token'        => $this->getToken()
        ];
    }
}
