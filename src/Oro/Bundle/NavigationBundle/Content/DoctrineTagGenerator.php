<?php

namespace Oro\Bundle\NavigationBundle\Content;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

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
        if (is_object($data)) {
            $class = ClassUtils::getClass($data);
        } elseif (is_string($data)) {
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
            $tags = [];

            if ($data instanceof FormInterface) {
                $data = $data->getData();

                return $this->generate($data, $includeCollectionTag, $processNestedData);
            }

            if (is_object($data)) {
                $tag = $this->convertToTag(ClassUtils::getClass($data));
                // tag only in case if it's not a new object
                if ($this->uow->getEntityState($data) !== UnitOfWork::STATE_NEW
                    && !$this->uow->isScheduledForInsert($data)
                ) {
                    $tags[] = implode('_', array_merge([$tag], $this->uow->getEntityIdentifier($data)));

                    if ($processNestedData) {
                        $tags = array_merge($tags, $this->collectNestedDataTags($data));
                    }
                }

                $class = ClassUtils::getClass($data);
            } elseif (is_string($data)) {
                $class = ClassUtils::getRealClass($data);
            }

            if ($includeCollectionTag) {
                $tags[] = $this->convertToTag($class) . self::COLLECTION_SUFFIX;
            }

            $this->generatedTags[$cacheKey] = $tags;
        }

        return $this->generatedTags[$cacheKey];
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    protected function collectNestedDataTags($data)
    {
        $tags = [];

        $metadata = $this->em->getClassMetadata(ClassUtils::getClass($data));
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
}
