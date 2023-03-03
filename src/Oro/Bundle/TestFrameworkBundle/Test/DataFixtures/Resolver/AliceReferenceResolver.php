<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

/**
 * AliceReferenceResolver parse reference path to return the appropriate value
 */
class AliceReferenceResolver implements ResolverInterface, ReferencesAwareInterface
{
    /**
     * @var string
     */
    private static $regex = '/^@(?P<ref>[^<*]*)$/';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var Collection
     */
    protected $references;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function setReferences(Collection $references)
    {
        $this->references = $references;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($value)
    {
        if (!\is_string($value) || !\preg_match(self::$regex, $value, $matches)) {
            return $value;
        }

        $reference = $matches['ref'] ?: null;
        $refParts = explode('->', $reference);
        $reference = array_shift($refParts);

        if (!$this->references->containsKey($reference)) {
            throw new \RuntimeException(sprintf('Reference "%s" not found', $reference));
        }

        $object = $this->references->get($reference);
        $result = $this->actualizeObject($object);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($refParts as $refPart) {
            if (preg_match('/\(/', $refPart)) {
                $result = $this->callObjectMethod($result, $refPart);
            } else {
                $result = $propertyAccessor->getValue($result, $refPart);
            }
        }

        return $result;
    }

    /**
     * @param object $object
     * @param string $refPart
     * @return mixed
     */
    private function callObjectMethod($object, $refPart)
    {
        $regexp = '/(?P<methodName>[a-zA-Z]*)\((?P<parameters>[^\)]*)\)/';
        preg_match($regexp, $refPart, $matches);

        $parameters = explode(',', $matches['parameters']);
        $parameters = array_map(function ($item) {
            return preg_replace('/^[\s\'"]*|[\s\'"]*$/', '', $item);
        }, $parameters);
        $method = $matches['methodName'];

        return call_user_func_array([$object, $method], $parameters);
    }

    /**
     * Reload the object, because it can be detached from doctrine by previous moves
     *
     * @param object $object
     * @return object
     */
    private function actualizeObject($object)
    {
        $class = get_class($object);
        $manager = $this->registry->getManagerForClass($class);

        if ($object instanceof Proxy && !$object->__isInitialized() && !$manager->contains($object)) {
            $identifier = $manager->getClassMetadata($class)->getIdentifierValues($object);
            $object = $manager->find($class, $identifier);
        }

        return $object;
    }
}
