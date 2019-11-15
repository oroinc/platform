<?php

namespace Oro\Bundle\DraftBundle\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Responsible for move data from draft entity to source entity, including only draftable fields.
 */
class Publisher
{
    /**
     * @var ConfigProvider
     */
    private $draftProvider;

    /**
     * @var EntityManager
     */
    private $entityManager;
    
    /**
     * @param ConfigProvider $draftProvider
     * @param EntityManager $entityManager
     */
    public function __construct(
        ConfigProvider $draftProvider,
        EntityManager $entityManager
    ) {
        $this->draftProvider = $draftProvider;
        $this->entityManager = $entityManager;
    }

    /**
     * @param DraftableInterface $source
     *
     * @return DraftableInterface
     */
    public function create(DraftableInterface $source): DraftableInterface
    {
        $accessor = new PropertyAccessor();
        $target = $source->getDraftSource();
        $properties = $this->getDraftableProperties($source);
        foreach ($properties as $property) {
            $value = $accessor->getValue($source, $property);
            $accessor->setValue($target, new PropertyPath($property), $value);
        }

        return $target;
    }

    /**
     * @param DraftableInterface $source
     *
     * @return array
     */
    private function getDraftableProperties(DraftableInterface $source): array
    {
        $className = ClassUtils::getRealClass($source);
        $metadata = $this->entityManager->getClassMetadata($className);
        $properties = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());

        return array_filter($properties, function ($property) use ($className) {
            return $this->isDraftable($className, $property);
        });
    }

    /**
     * @param string $className
     * @param string $property
     *
     * @return bool
     */
    private function isDraftable(string $className, string $property): bool
    {
        if ($this->draftProvider->hasConfig($className, $property)) {
            return $this->draftProvider->getConfig($className, $property)->is('draftable');
        }

        return false;
    }
}
