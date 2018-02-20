<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Generates a fallback entity name from entity id
 * e.g. 'Item #123'
 *
 * Note: Keep this provider with the lowest priority
 */
class FallbackEntityNameProvider implements EntityNameProviderInterface
{
    const TRANSLATION_KEY = 'oro.entity.item';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ManagerRegistry $doctrine
     * @param TranslatorInterface $translator
     */
    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        $fieldName = $this->getFieldName(ClassUtils::getClass($entity));

        if (!$fieldName) {
            return false;
        }

        $entityId = $this->getFieldValue($entity, $fieldName);

        if ($format === self::SHORT) {
            return $entityId;
        }

        if ($format === self::FULL) {
            return $this->translator->trans(self::TRANSLATION_KEY, ['%id%' => $entityId]);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        $fieldName = $this->getFieldName($className);

        if (!$fieldName) {
            return false;
        }

        if ($format === self::SHORT) {
            if ($fieldName) {
                return $alias . '.' . $fieldName;
            }
        }

        if ($format === self::FULL) {
            // replace translation placeholder with identifier fieldName
            $fallbackValue = str_replace(
                '%id%',
                sprintf("', %s.%s, '", $alias, $fieldName),
                $this->translator->trans(self::TRANSLATION_KEY)
            );

            return sprintf("CONCAT('%s')", $fallbackValue);
        }

        return false;
    }

    /**
     * Return single class Identifier Field Name or null if there a multiple or none
     *
     * @param $className
     *
     * @return string|null
     */
    protected function getFieldName($className)
    {
        $manager = $this->doctrine->getManagerForClass($className);
        if (null === $manager) {
            return null;
        }

        $metadata = $manager->getClassMetadata($className);

        $identifierFieldNames = $metadata->getIdentifierFieldNames();
        if (count($identifierFieldNames) !== 1) {
            return null;
        }

        return reset($identifierFieldNames);
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getFieldValue($entity, $fieldName)
    {
        $getterName = 'get' . Inflector::classify($fieldName);

        if (method_exists($entity, $getterName)) {
            return $entity->{$getterName}();
        }

        if (isset($entity->{$fieldName})) {
            return $entity->{$fieldName};
        }

        return null;
    }
}
