<?php

namespace Oro\Bundle\EntityBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;

class RelationHelper
{
    /** @var VirtualRelationProviderInterface */
    protected $virtualRelationProvider;

    /** @var array */
    protected $joins = [];

    /**
     * @param VirtualRelationProviderInterface $provider
     */
    public function __construct(VirtualRelationProviderInterface $provider)
    {
        $this->virtualRelationProvider = $provider;
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function hasVirtualRelations($entityClass)
    {
        return count($this->getVirtualJoins($entityClass)) > 0;
    }

    /**
     * @param string $entityClass
     * @param string $targetEntityClass
     * @return int
     */
    public function getMetadataTypeForVirtualJoin($entityClass, $targetEntityClass)
    {
        $result = 0;

        $joins = $this->getVirtualJoins($entityClass);
        foreach ($joins as $join) {
            if ($join['join'] === $targetEntityClass) {
                $result = $join['type'];
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    protected function getVirtualJoins($entityClass)
    {
        if (!array_key_exists($entityClass, $this->joins)) {
            $this->joins[$entityClass] = [];
            $relations = $this->virtualRelationProvider->getVirtualRelations($entityClass);

            foreach ($relations as $relation) {
                if (!isset($relation['query']['join'])) {
                    continue;
                }

                foreach ($relation['query']['join'] as $joins) {
                    foreach ($joins as $join) {
                        if (!class_exists($join['join'])) {
                            continue;
                        }
                        $this->joins[$entityClass][] = [
                            'type' => $this->getMetadataType($relation['relation_type']),
                            'join' => $join['join'],
                        ];
                    }
                }
            }
        }

        return $this->joins[$entityClass];
    }

    /**
     * @param string $type
     * @return int
     */
    protected function getMetadataType($type)
    {
        $metadataType = 0;
        switch (strtolower($type)) {
            case 'onetoone':
                $metadataType = ClassMetadata::ONE_TO_ONE;
                break;
            case 'manytoone':
                $metadataType = ClassMetadata::MANY_TO_ONE;
                break;
            case 'onetomany':
                $metadataType = ClassMetadata::ONE_TO_MANY;
                break;
            case 'manytomany':
                $metadataType = ClassMetadata::MANY_TO_MANY;
                break;
        }

        return $metadataType;
    }
}
