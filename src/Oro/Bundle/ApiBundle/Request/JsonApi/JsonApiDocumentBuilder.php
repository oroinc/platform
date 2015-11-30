<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;

class JsonApiDocumentBuilder
{
    const JSONAPI       = 'jsonapi';
    const LINKS         = 'links';
    const META          = 'meta';
    const ERRORS        = 'errors';
    const DATA          = 'data';
    const INCLUDED      = 'included';
    const ATTRIBUTES    = 'attributes';
    const RELATIONSHIPS = 'relationships';
    const ID            = 'id';
    const TYPE          = 'type';

    /** @var EntityClassTransformerInterface */
    protected $entityClassTransformer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var array */
    protected $result = [];

    /**
     * @param EntityClassTransformerInterface $entityClassTransformer
     * @param EntityIdTransformerInterface    $entityIdTransformer
     */
    public function __construct(
        EntityClassTransformerInterface $entityClassTransformer,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->entityClassTransformer = $entityClassTransformer;
        $this->entityIdTransformer    = $entityIdTransformer;
    }

    /**
     * Returns built JSON API document.
     *
     * @return array
     */
    public function getDocument()
    {
        return $this->result;
    }

    /**
     * Sets a single object as the primary data.
     *
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return self
     */
    public function setDataObject($object, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = null;
        if (null !== $object) {
            $this->result[self::DATA] = $this->handleObject($object, $metadata);
        }

        return $this;
    }

    /**
     * Sets a collection as the primary data.
     *
     * @param mixed               $collection
     * @param EntityMetadata|null $metadata
     *
     * @return self
     */
    public function setDataCollection($collection, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = [];
        if (is_array($collection) || $collection instanceof \Traversable) {
            foreach ($collection as $object) {
                $this->result[self::DATA][] = $this->handleObject($object, $metadata);
            }
        } else {
            throw new \UnexpectedValueException(
                sprintf(
                    'Expected argument of type "array or \Traversable", "%s" given',
                    is_object($collection) ? get_class($collection) : gettype($collection)
                )
            );
        }

        return $this;
    }

    /**
     * Adds an object related to the primary data and/or another related object.
     *
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return self
     */
    public function addRelatedObject($object, EntityMetadata $metadata = null)
    {
        $this->result[self::INCLUDED][] = $this->handleObject($object, $metadata);

        return $this;
    }

    /**
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function handleObject($object, EntityMetadata $metadata = null)
    {
        if (is_array($object)) {
            $result = $this->handleArrayObject($object, $metadata);
        } else {
            throw new \UnexpectedValueException(
                sprintf(
                    'Expected argument of type "array", "%s" given',
                    is_object($object) ? get_class($object) : gettype($object)
                )
            );
        }

        return $result;
    }

    /**
     * @param array               $object
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function handleArrayObject(array $object, EntityMetadata $metadata = null)
    {
        $result = [];
        if (null === $metadata) {
            $result[self::ATTRIBUTES] = $object;
        } else {
            $result[self::TYPE] = $this->getEntityType($metadata->getClassName());
            $result[self::ID]   = $this->getEntityIdFromArrayObject($object, $metadata);
            foreach ($object as $name => $value) {
                if (in_array($name, $metadata->getIdentifierFieldNames(), true)) {
                    continue;
                }
                if ($metadata->hasAssociation($name)) {
                    $associationMetadata = $metadata->getAssociation($name);

                    $result[self::RELATIONSHIPS][$name][self::DATA] = $associationMetadata->isCollection()
                        ? $this->processRelatedArrayCollection($associationMetadata, $value)
                        : $this->processRelatedArrayObject($associationMetadata, $value);
                } else {
                    $result[self::ATTRIBUTES][$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param AssociationMetadata $associationMetadata
     * @param mixed               $value
     *
     * @return array|null
     */
    protected function processRelatedArrayObject(AssociationMetadata $associationMetadata, $value)
    {
        $targetMetadata   = $associationMetadata->getTargetMetadata();
        $targetEntityType = $this->getEntityType($associationMetadata->getTargetClassName());

        $data = null;
        if (null !== $value) {
            if (null === $targetMetadata) {
                $data = $this->getResourceIdObject($targetEntityType, $value);
            } else {
                $data = $this->getResourceIdObject(
                    $targetEntityType,
                    $this->getEntityIdFromArrayObject($value, $targetMetadata)
                );
                $this->addRelatedObject($value, $targetMetadata);
            }
        }

        return $data;
    }

    /**
     * @param AssociationMetadata $associationMetadata
     * @param mixed               $value
     *
     * @return array
     */
    protected function processRelatedArrayCollection(AssociationMetadata $associationMetadata, $value)
    {
        $targetMetadata   = $associationMetadata->getTargetMetadata();
        $targetEntityType = $this->getEntityType($associationMetadata->getTargetClassName());

        $data = [];
        if (null !== $value) {
            if (null === $targetMetadata) {
                foreach ($value as $val) {
                    $data[] = $this->getResourceIdObject($targetEntityType, $val);
                }
            } else {
                foreach ($value as $val) {
                    $data[] = $this->getResourceIdObject(
                        $targetEntityType,
                        $this->getEntityIdFromArrayObject($val, $targetMetadata)
                    );
                    $this->addRelatedObject($val, $targetMetadata);
                }
            }
        }

        return $data;
    }

    /**
     * @param array          $object
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    protected function getEntityIdFromArrayObject(array $object, EntityMetadata $metadata)
    {
        $result = null;

        $idFieldNames      = $metadata->getIdentifierFieldNames();
        $idFieldNamesCount = count($idFieldNames);
        if ($idFieldNamesCount === 1) {
            $fieldName = reset($idFieldNames);
            if (!array_key_exists($fieldName, $object)) {
                throw new \RuntimeException(
                    sprintf(
                        'An object of the type "%s" does not have the identifier property "%s".',
                        $metadata->getClassName(),
                        $fieldName
                    )
                );
            }
            $result = $this->entityIdTransformer->transform($object[$fieldName]);
        } elseif ($idFieldNamesCount > 1) {
            $id = [];
            foreach ($idFieldNames as $fieldName) {
                if (!array_key_exists($fieldName, $object)) {
                    throw new \RuntimeException(
                        sprintf(
                            'An object of the type "%s" does not have the identifier property "%s".',
                            $metadata->getClassName(),
                            $fieldName
                        )
                    );
                }
                $id[$fieldName] = $object[$fieldName];
            }
            $result = $this->entityIdTransformer->transform($id);
        } else {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" entity does not have an identifier.',
                    $metadata->getClassName()
                )
            );
        }

        if (empty($result)) {
            throw new \RuntimeException(
                sprintf(
                    'The identifier value for "%s" entity must not be empty.',
                    $metadata->getClassName()
                )
            );
        }

        return $result;
    }

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return array
     */
    protected function getResourceIdObject($entityType, $entityId)
    {
        return [
            self::TYPE => $entityType,
            self::ID   => $entityId
        ];
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getEntityType($entityClass)
    {
        return $this->entityClassTransformer->transform($entityClass);
    }

    /**
     * Checks that the primary data does not exist.
     */
    protected function assertNoData()
    {
        if (array_key_exists(self::DATA, $this->result)) {
            throw new \RuntimeException('A primary data already exist.');
        }
    }
}
