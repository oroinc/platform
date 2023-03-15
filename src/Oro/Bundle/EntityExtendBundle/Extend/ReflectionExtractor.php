<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldAccessorsHelper;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor as BasicReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Extended class reflection extractor that can delegate property accessibility check
 * to a class that supports such option.
 */
class ReflectionExtractor extends BasicReflectionExtractor
{
    private Inflector $inflector;

    public function __construct(
        array $mutatorPrefixes = null,
        array $accessorPrefixes = null,
        array $arrayMutatorPrefixes = null,
        bool $enableConstructorExtraction = true,
        int $accessFlags = BasicReflectionExtractor::ALLOW_PUBLIC,
        InflectorInterface $inflector = null,
        int $magicMethodsFlags = BasicReflectionExtractor::ALLOW_MAGIC_GET | BasicReflectionExtractor::ALLOW_MAGIC_SET
    ) {
        parent::__construct(
            $mutatorPrefixes,
            $accessorPrefixes,
            $arrayMutatorPrefixes,
            $enableConstructorExtraction,
            $accessFlags,
            $inflector,
            $magicMethodsFlags
        );
        $this->inflector = InflectorFactory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function getReadInfo(string $class, string $property, array $context = []): ?PropertyReadInfo
    {
        $info = parent::getReadInfo($class, $property, $context);

        if ($info === null ||
            $info->getType() !== PropertyWriteInfo::TYPE_PROPERTY ||
            !is_subclass_of($class, ExtendEntityInterface::class)) {
            return $info;
        }

        $info = $this->getInfo($info, $class, $property);
        if (null !== $info) {
            return $info;
        }
        $camelized = $this->camelize($property);
        foreach (['get', 'is', 'has', 'can'] as $prefix) {
            $methodName = $prefix . $camelized;
            if (EntityPropertyInfo::extendedMethodExists($class, $methodName)) {
                return new PropertyReadInfo(
                    PropertyReadInfo::TYPE_METHOD,
                    $methodName,
                    PropertyReadInfo::VISIBILITY_PUBLIC,
                    false,
                    false
                );
            }
        }

        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function getWriteInfo(string $class, string $property, array $context = []): ?PropertyWriteInfo
    {
        $info = parent::getWriteInfo($class, $property, $context);
        if ($info === null ||
            $info->getType() !== PropertyWriteInfo::TYPE_PROPERTY ||
            !is_subclass_of($class, ExtendEntityInterface::class)) {
            return $info;
        }

        [$adderAccessName, $removerAccessName] = $this->findAdderAndRemover($class, $property, $context);
        if (null !== $adderAccessName && null !== $removerAccessName) {
            $mutator = new PropertyWriteInfo(PropertyWriteInfo::TYPE_ADDER_AND_REMOVER);
            $mutator->setAdderInfo(
                new PropertyWriteInfo(
                    PropertyWriteInfo::TYPE_METHOD,
                    $adderAccessName,
                    PropertyWriteInfo::VISIBILITY_PUBLIC,
                    false
                )
            );
            $mutator->setRemoverInfo(
                new PropertyWriteInfo(
                    PropertyWriteInfo::TYPE_METHOD,
                    $removerAccessName,
                    PropertyWriteInfo::VISIBILITY_PUBLIC,
                    false
                )
            );

            return $mutator;
        }

        $info = $this->getInfo($info, $class, $property);
        if (null !== $info) {
            return $info;
        }

        $setterName = EntityFieldAccessorsHelper::setterName($property);
        if (EntityPropertyInfo::extendedMethodExists($class, $setterName)) {
            $matchedMethodName = EntityPropertyInfo::getMatchedMethod($class, $setterName);
            return new PropertyWriteInfo(
                PropertyWriteInfo::TYPE_METHOD,
                $matchedMethodName,
                PropertyWriteInfo::VISIBILITY_PUBLIC,
                false
            );
        }

        $failedInfo = new PropertyWriteInfo(PropertyWriteInfo::TYPE_NONE, $property);
        $errors = [sprintf("There is no %s property at %s", $property, $class)];
        $failedInfo->setErrors($errors);

        return $failedInfo;
    }

    private function findAdderAndRemover(string $class, string $property, array $context): array
    {
        $allowAdderRemover = $context['enable_adder_remover_extraction'] ?? true;
        if (!$allowAdderRemover) {
            return [null, null];
        }
        $addMethod = EntityFieldAccessorsHelper::adderName($property);
        $removeMethod = EntityFieldAccessorsHelper::removerName($property);
        $addMethodFound = EntityPropertyInfo::methodExists($class, $addMethod);
        $removeMethodFound = EntityPropertyInfo::methodExists($class, $removeMethod);

        if ($addMethodFound && $removeMethodFound) {
            return [$addMethod, $removeMethod];
        }

        return [null, null];
    }

    /**
     * @param PropertyReadInfo|PropertyWriteInfo $info
     * @param string $class
     * @param string $property
     * @return PropertyReadInfo|PropertyWriteInfo|null
     */
    private function getInfo($info, string $class, string $property)
    {
        if (EntityPropertyInfo::propertyExists($class, $property)) {
            return $info;
        }
        $alternativeProperty = $this->getAlternativePropertyName($property);

        if (null === $info) {
            return null;
        }
        $infoClass = $info::class;
        if (EntityPropertyInfo::propertyExists($class, $alternativeProperty)) {
            return new $infoClass(
                PropertyWriteInfo::TYPE_PROPERTY,
                $alternativeProperty,
                PropertyWriteInfo::VISIBILITY_PUBLIC,
                false,
                false
            );
        }
        if (EntityPropertyInfo::methodExists($class, $property)) {
            return new $infoClass(
                PropertyWriteInfo::TYPE_METHOD,
                $property,
                PropertyWriteInfo::VISIBILITY_PUBLIC,
                false,
                false
            );
        }

        return null;
    }

    private function getAlternativePropertyName(string $property): string
    {
        if (str_contains($property, '_')) {
            return $this->camelize($property);
        }

        return $this->inflector->tableize($property);
    }

    private function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
}
