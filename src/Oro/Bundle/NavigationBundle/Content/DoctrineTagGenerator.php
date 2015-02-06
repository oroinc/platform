<?php

namespace Oro\Bundle\NavigationBundle\Content;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class DoctrineTagGenerator implements TagGeneratorInterface
{
    /** @var array */
    protected $generatedTags = [];

    /** @var  EntityClassResolver */
    protected $resolver;

    /** @var UnitOfWork */
    protected $uow;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager       $em
     * @param EntityClassResolver $resolver
     */
    public function __construct(EntityManager $em, EntityClassResolver $resolver)
    {
        $this->uow      = $em->getUnitOfWork();
        $this->em       = $em;
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data)
    {
        if ($data instanceof FormInterface) {
            $data = $data->getData();

            return $this->supports($data);
        }

        $class = false;
        if (is_object($data) || is_string($data)) {
            $class = ClassUtils::getRealClass($data);
        }

        return $class && $this->resolver->isEntity($class);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        $cacheKey = $this->getCacheIdentifier($data);
        if (!isset($this->generatedTags[$cacheKey])) {
            if ($data instanceof FormInterface) {
                return $this->generate($data->getData(), $includeCollectionTag, $processNestedData);
            }

            $this->generatedTags[$cacheKey] = $this->getTags($data, $includeCollectionTag, $processNestedData);
        }

        return $this->generatedTags[$cacheKey];
    }

    /**
     * @param mixed $data
     * @param bool $includeCollectionTag
     * @param bool $processNestedData
     * @return array
     */
    protected function getTags($data, $includeCollectionTag, $processNestedData)
    {
        $tags = [];

        $class = ClassUtils::getRealClass($data);

        if (is_object($data)) {
            // tag only in case if it's not a new object
            if ($this->isNewEntity($data)) {
                $identifier = $this->uow->getEntityIdentifier($data);
                $tag = $this->convertToTag($class);
                $tags[] = implode('_', array_merge([$tag], $identifier));

                if ($processNestedData) {
                    $tags = array_merge($tags, $this->collectNestedDataTags($data));
                }
            }
        }

        if ($includeCollectionTag && $class !== null) {
            $tags[] = $this->convertToTag($class) . self::COLLECTION_SUFFIX;
        }

        return $tags;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    protected function collectNestedDataTags($data)
    {
        $tags = [];

        $metadata = $this->em->getClassMetadata(ClassUtils::getRealClass($data));
        foreach ($metadata->getAssociationMappings() as $field => $assoc) {
            $value = $metadata->reflFields[$field]->getValue($data);
            if (null !== $value) {
                // skip DoctrineTagGenerator#supports() call due to doctrine association mapping always contain entities
                $unwrappedValue = ($assoc['type'] & ClassMetadata::TO_ONE) ? [$value] : $value->unwrap();
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
        return is_string($data) ? $this->convertToTag($data) : spl_object_hash($data);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isNewEntity($entity)
    {
        $entityState = $this->uow->getEntityState($entity);

        return $entityState !== UnitOfWork::STATE_NEW
            && $entityState !== UnitOfWork::STATE_DETACHED
            && !$this->uow->isScheduledForInsert($entity);
    }
}
