<?php

namespace Oro\Bundle\NavigationBundle\Content;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Form\FormInterface;

class DoctrineTagGenerator implements TagGeneratorInterface
{
    /** @var array */
    protected $generatedTags = [];

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data)
    {
        if (null === $data) {
            return false;
        }

        if ($data instanceof FormInterface) {
            $data = $data->getData();

            return null !== $data && $this->supports($data);
        }

        if (is_object($data)) {
            return null !== $this->doctrine->getManagerForClass(ClassUtils::getClass($data));
        } elseif (is_string($data)) {
            return null !== $this->doctrine->getManagerForClass(ClassUtils::getRealClass($data));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        $cacheKey = $this->getCacheIdentifier($data);
        if (isset($this->generatedTags[$cacheKey])) {
            return $this->generatedTags[$cacheKey];
        }

        if ($data instanceof FormInterface) {
            return $this->generate($data->getData(), $includeCollectionTag, $processNestedData);
        }

        $tags = $this->getTags($data, $includeCollectionTag, $processNestedData);

        $this->generatedTags[$cacheKey] = $tags;

        return $tags;
    }

    /**
     * @param mixed $data
     * @param bool  $includeCollectionTag
     * @param bool  $processNestedData
     *
     * @return array
     */
    protected function getTags($data, $includeCollectionTag, $processNestedData)
    {
        $tags = [];

        if (is_object($data)) {
            $class = ClassUtils::getClass($data);
            /** @var EntityManager $em */
            $em  = $this->doctrine->getManagerForClass($class);
            $uow = $em->getUnitOfWork();
            // tag only in case if it's not a new object
            if ($this->isNewEntity($data, $uow)) {
                $tags[] = implode(
                    '_',
                    array_merge([$this->convertToTag($class)], $uow->getEntityIdentifier($data))
                );
                if ($processNestedData) {
                    $tags = array_merge(
                        $tags,
                        $this->collectNestedDataTags($data, $em->getClassMetadata($class))
                    );
                }
            }
        } else {
            $class = ClassUtils::getRealClass($data);
        }

        if ($includeCollectionTag) {
            $tags[] = $this->convertToTag($class) . self::COLLECTION_SUFFIX;
        }

        return $tags;
    }

    /**
     * @param mixed         $data
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function collectNestedDataTags($data, ClassMetadata $metadata)
    {
        $tags = [];

        foreach ($metadata->getAssociationMappings() as $fieldName => $mapping) {
            $value = $metadata->reflFields[$fieldName]->getValue($data);
            if (null !== $value) {
                // skip DoctrineTagGenerator#supports() call due to doctrine association mapping always contain entities
                if ($mapping['type'] & ClassMetadata::TO_ONE) {
                    $unwrappedValue = [$value];
                } elseif ($value instanceof PersistentCollection) {
                    $unwrappedValue = $value->unwrap();
                } else {
                    $unwrappedValue = $value;
                }
                foreach ($unwrappedValue as $entity) {
                    // allowed one nested level
                    $tags = array_merge($tags, $this->generate($entity));
                }
            }
        }

        return $tags;
    }

    /**
     * Convert entity FQCN to tag
     *
     * @param string $data
     *
     * @return string
     */
    protected function convertToTag($data)
    {
        return preg_replace('#[^a-z]+#i', '_', $data);
    }

    /**
     * Generates cache identifier
     *
     * @param mixed $data
     *
     * @return string
     */
    protected function getCacheIdentifier($data)
    {
        return is_string($data)
            ? $this->convertToTag($data)
            : spl_object_hash($data);
    }

    /**
     * @param object     $entity
     * @param UnitOfWork $uow
     *
     * @return bool
     */
    protected function isNewEntity($entity, UnitOfWork $uow)
    {
        $entityState = $uow->getEntityState($entity);

        return
            $entityState !== UnitOfWork::STATE_NEW
            && $entityState !== UnitOfWork::STATE_DETACHED
            && !$uow->isScheduledForInsert($entity);
    }
}
