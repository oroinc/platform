<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\ORM\Proxy\Proxy;
use Nelmio\Alice\Instances\Collection;
use Nelmio\Alice\Instances\Processor\Methods\MethodInterface;
use Nelmio\Alice\Instances\Processor\ProcessableInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * AliceReferenceProcessor parse reference path to return the appropriate value
 */
class AliceReferenceProcessor implements MethodInterface
{
    private static $regex = '/^@(?P<ref>[^<*]*)$/';

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var Collection
     */
    protected $objects;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Sets the object collection to handle referential calls.
     *
     * @param Collection $objects
     */
    public function setObjects(Collection $objects)
    {
        $this->objects = $objects;
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(ProcessableInterface $processable)
    {
        return
            is_string($processable->getValue())
            && $processable->valueMatches(static::$regex)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ProcessableInterface $processable, array $variables)
    {
        $reference = null !== ($processable->getMatch('ref')) ? $processable->getMatch('ref') : null;
        $refParts = explode('->', $reference);
        $reference = array_shift($refParts);

        if (!$this->objects->containsKey($reference)) {
            throw new \Exception(sprintf('Reference "%s" not found', $reference));
        }

        $object = $this->objects->find($reference);
        $result = $this->actualizeObject($object);
        $propertyAccessor = new PropertyAccessor();

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
