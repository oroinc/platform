<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Validator\Mapping;

use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Mapping\MemberMetadata;

/**
 * Stores all metadata needed for validating a class property via its getter method.
 *
 * @see \Symfony\Component\Validator\Mapping\GetterMetadata
 */
class GetterMetadata extends MemberMetadata
{
    private const METHOD_NOT_EXISTS = 0;
    private const METHOD_REAL       = 1;
    private const METHOD_VIRTUAL    = 2;

    private int $methodType = self::METHOD_NOT_EXISTS;

    public function __construct(string $class, string $property, string $method = null)
    {
        if (null === $method) {
            $getMethod = 'get' . ucfirst($property);
            $isMethod  = 'is' . ucfirst($property);
            $hasMethod = 'has' . ucfirst($property);

            if (method_exists($class, $getMethod)) {
                $this->methodType = self::METHOD_REAL;
                $method = $getMethod;
            } elseif (method_exists($class, $isMethod)) {
                $this->methodType = self::METHOD_REAL;
                $method = $isMethod;
            } elseif (method_exists($class, $hasMethod)) {
                $this->methodType = self::METHOD_REAL;
                $method = $hasMethod;
            } elseif (null !== ($extendedMethod = $this->getExtendEntityMethod($class, $property))) {
                $this->methodType = self::METHOD_VIRTUAL;
                $method = $extendedMethod;
            } else {
                $format = 'Neither of these methods exist in class "%s": "%s", "%s", "%s".';
                throw new ValidatorException(sprintf($format, $class, $getMethod, $isMethod, $hasMethod));
            }
        } else {
            if (method_exists($class, $method)) {
                $this->methodType = self::METHOD_REAL;
            } elseif (null !== ($extendedMethod = $this->getExtendEntityMethod($class, $property, $method))) {
                $this->methodType = self::METHOD_VIRTUAL;
            } else {
                throw new ValidatorException(
                    sprintf('The "%s()" method does not exist in class "%s".', $method, $class)
                );
            }
        }

        parent::__construct($class, $method, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), ['methodType']);
    }

    protected function getExtendEntityMethod(string $class, string $property, string $method = null): ?string
    {
        if (is_subclass_of($class, ExtendEntityInterface::class)) {
            $transport = new EntityFieldProcessTransport();
            $transport->setClass($class);
            $transport->setValue(EntityFieldProcessTransport::EXISTS_METHOD);

            if ($method === null) {
                foreach (['get', 'is', 'has'] as $prefix) {
                    $method = $prefix . \ucfirst($property);
                    $transport->setName($method);

                    ExtendedEntityFieldsProcessor::executeMethodExists($transport);

                    if ($transport->isProcessed() && $transport->getResult()) {
                        return $method;
                    }
                }
            } else {
                $transport->setName($method);

                ExtendedEntityFieldsProcessor::executeMethodExists($transport);

                if ($transport->isProcessed() && $transport->getResult()) {
                    return $method;
                }
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function newReflectionMember($objectOrClassName)
    {
        if ($this->methodType === self::METHOD_REAL) {
            return new \ReflectionMethod($objectOrClassName, $this->getName());
        }

        return ReflectionVirtualProperty::create($this->getPropertyName());
    }

    /**
     * @inheritDoc
     */
    public function getPropertyValue($containingValue)
    {
        if ($this->methodType === self::METHOD_REAL) {
            return $this->newReflectionMember($containingValue)->invoke($containingValue);
        } elseif ($this->methodType === self::METHOD_VIRTUAL) {
            return call_user_func([$containingValue, $this->getName()]);
        }
    }
}
