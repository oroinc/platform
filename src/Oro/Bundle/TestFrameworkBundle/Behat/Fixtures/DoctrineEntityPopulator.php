<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nelmio\Alice\Fixtures\Fixture;
use Nelmio\Alice\Instances\Populator\Methods\MethodInterface;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;

class DoctrineEntityPopulator implements MethodInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function canSet(Fixture $fixture, $object, $property, $value)
    {
        if (false === $this->isEntity(get_class($object))) {
            return false;
        }

        $metadata = $this->getEm()->getClassMetadata(get_class($object));

        if (false === $metadata->hasAssociation($property)) {
            return false;
        }

        $assocType = $metadata->getAssociationMapping($property)['type'];

        if ($assocType & ClassMetadata::TO_ONE) {
            return !is_object($value);
        }

        if ($assocType & ClassMetadata::TO_MANY) {
            if (!is_array($value)) {
                return false;
            }

            return !$this->isArrayOfObjects($value);
        }

        throw new RuntimeException(sprintf('Unknown type of "%s" association', $property));
    }

    /**
     * {@inheritdoc}
     */
    public function set(Fixture $fixture, $object, $property, $value)
    {
        $metadata = $this->getEm()->getClassMetadata(get_class($object));
        $assocType = $metadata->getAssociationMapping($property)['type'];
        $propertyMeta = $metadata->getAssociationMapping($property);

        if ($assocType & ClassMetadata::TO_ONE) {
            $newValue = $this->findObject($propertyMeta['targetEntity'], $value);
        }

        if ($assocType & ClassMetadata::TO_MANY) {
            $newValue = array_map(function ($element) use ($propertyMeta) {
                if (is_object($element)) {
                    return $element;
                }

                return $this->findObject($propertyMeta['targetEntity'], $element);
            }, $value);
        }

        $propertyAccessor = new PropertyAccessor();
        $propertyAccessor->setValue($object, $property, $newValue);
    }

    /**
     * @return EntityManager
     */
    private function getEm()
    {
        return $this->kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
    * @param string|object $class
    *
    * @return boolean
    */
    private function isEntity($class)
    {
        if (is_object($class)) {
            $class = ClassUtils::getClass($class);
        }

        return ! $this->getEm()->getMetadataFactory()->isTransient($class);
    }

    /**
     * @param array $value
     * @return bool
     */
    private function isArrayOfObjects(array $value)
    {
        return array_reduce($value, function ($carry, $item) {
            return is_object($item) && $carry;
        }, true);
    }

    /**
     * @param string $class
     * @param string|int|array $id
     * @return object
     */
    private function findObject($class, $id)
    {
        $targetObject = $this->getEm()
            ->getRepository($class)
            ->find($id);

        if (!$targetObject) {
            throw new RuntimeException(sprintf(
                'Entity "%s" with identifier "%s" not found',
                $class,
                $id
            ));
        }

        return $targetObject;
    }
}
