<?php

namespace Oro\Bundle\NavigationBundle\Content;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class DoctrineTagGenerator implements TagGeneratorInterface
{
    /** @var  EntityClassResolver */
    protected $resolver;

    /** @var UnitOfWork */
    protected $uow;

    public function __construct(EntityManager $em, EntityClassResolver $resolver)
    {
        $this->uow      = $em->getUnitOfWork();
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data)
    {
        if ($data instanceof FormInterface) {
            $data = $data->getData();
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
    public function generate($data, $includeCollectionTag = false)
    {
        $tags = [];

        if ($data instanceof FormInterface) {
            $data = $data->getData();
        }

        $class = false;
        if (is_object($data)) {
            $tag = $this->convertToTag(ClassUtils::getClass($data));

            // tag only in case if it's not a new object
            if ($this->uow->getEntityState($data) !== UnitOfWork::STATE_NEW) {
                $tags[] = implode('_', array_merge([$tag], $this->uow->getEntityIdentifier($data)));
            }

            $class = ClassUtils::getClass($data);
        } elseif (is_string($data)) {
            $class = ClassUtils::getRealClass($data);
        }

        if ($includeCollectionTag && false !== $class) {
            $tags[] = $this->convertToTag($class) . self::COLLECTION_SUFFIX;
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
}
