<?php

namespace Oro\Bundle\ActivityBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ContextsToViewTransformer implements DataTransformerInterface
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var TranslatorInterface */
    protected $translator;

    /* @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var bool */
    protected $collectionModel;

    /**
     * @param EntityManager         $entityManager
     * @param TokenStorageInterface $securityTokenStorage
     * @param bool                  $collectionModel True if result should be Collection instead of array
     */
    public function __construct(
        EntityManager $entityManager,
        TokenStorageInterface $securityTokenStorage,
        $collectionModel = false
    ) {
        $this->entityManager = $entityManager;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->collectionModel = $collectionModel;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return '';
        }

        if (is_array($value) || $value instanceof Collection) {
            $result = [];
            foreach ($value as $target) {
                $targetClass = ClassUtils::getClass($target);

                $result[] = json_encode(
                    [
                        'entityClass' => $targetClass,
                        'entityId'    => $target->getId(),
                    ]
                );
            }

            $value = implode(';', $result);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        $targets = explode(';', $value);
        $result  = [];
        $filters = [];

        foreach ($targets as $target) {
            $target = json_decode($target, true);
            if (array_key_exists('entityClass', $target) === true && array_key_exists('entityId', $target)) {
                if (!isset($filters[$target['entityClass']])) {
                    $filters[$target['entityClass']] = [];
                }
                $filters[$target['entityClass']][] = $target['entityId'];
            }
        }

        foreach ($filters as $entityClass => $ids) {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            $entities = $this->entityManager->getRepository($metadata->getName())->findBy(
                ['id' => $ids]
            );
            $result   = array_merge($result, $entities);
        }

        if ($this->collectionModel) {
            $result = new ArrayCollection($result);
        }

        return $result;
    }
}
