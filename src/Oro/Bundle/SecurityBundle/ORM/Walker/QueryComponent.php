<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Represents a additional info for aliased AST expression,
 * such as "entityAlias.fieldName AS fieldAlias" or "parentEntityAlias.associationName AS entityAlias".
 */
class QueryComponent
{
    /** @var ClassMetadata */
    private $metadata;

    /** @var string|null */
    private $parent;

    /** @var array|null */
    private $relation;

    /** @var string|null */
    private $map;

    /** @var int|null */
    private $nestingLevel;

    /** @var array|null */
    private $token;

    /**
     * Generates and returns query component from array representation.
     *
     * @param array $componentArray
     *
     * @return QueryComponent|null
     */
    public static function fromArray(array $componentArray): ?QueryComponent
    {
        // Check if given query component is the table data.
        if (!array_key_exists('metadata', $componentArray)) {
            return null;
        }

        $result = new self();
        $result->setMetadata($componentArray['metadata']);
        $result->setParent($componentArray['parent']);
        $result->setRelation($componentArray['relation']);
        $result->setMap($componentArray['map']);
        $result->setNestingLevel($componentArray['nestingLevel']);
        $result->setToken($componentArray['token']);

        return $result;
    }

    /**
     * @return ClassMetadata
     */
    public function getMetadata(): ClassMetadata
    {
        return $this->metadata;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public function setMetadata(ClassMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return null|string
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * @param null|string $parent
     */
    public function setParent(?string $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return array|null
     */
    public function getRelation(): ?array
    {
        return $this->relation;
    }

    /**
     * @param array|null $relation
     */
    public function setRelation(?array $relation): void
    {
        $this->relation = $relation;
    }

    /**
     * @return string|null
     */
    public function getMap(): ?string
    {
        return $this->map;
    }

    /**
     * @param string|null $map
     */
    public function setMap(?string $map): void
    {
        $this->map = $map;
    }

    /**
     * @return int|null
     */
    public function getNestingLevel(): ?int
    {
        return $this->nestingLevel;
    }

    /**
     * @param int|null $nestingLevel
     */
    public function setNestingLevel(?int $nestingLevel): void
    {
        $this->nestingLevel = $nestingLevel;
    }

    /**
     * @return array|null
     */
    public function getToken(): ?array
    {
        return $this->token;
    }

    /**
     * @param array|null $token
     */
    public function setToken(?array $token): void
    {
        $this->token = $token;
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
            'parent'       => $this->getParent(),
            'relation'     => $this->getRelation(),
            'map'          => $this->getMap(),
            'nestingLevel' => $this->getNestingLevel(),
            'token'        => $this->getToken()
        ];
    }
}
