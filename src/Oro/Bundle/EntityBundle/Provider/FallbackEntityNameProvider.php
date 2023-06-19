<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Inflector\Inflector;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    private Inflector $inflector;

    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator, Inflector $inflector)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->inflector = $inflector;
    }

    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        $fieldName = $this->getFieldName(ClassUtils::getClass($entity));
        if (!$fieldName) {
            return false;
        }

        $entityId = $this->getFieldValue($entity, $fieldName);

        if (self::SHORT === $format) {
            return (string)$entityId;
        }

        if (self::FULL === $format) {
            return $this->trans(self::TRANSLATION_KEY, ['%id%' => $entityId], $locale);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        $fieldName = $this->getFieldName($className);
        if (!$fieldName) {
            return false;
        }

        if (self::SHORT === $format) {
            return sprintf('CAST(%s.%s AS string)', $alias, $fieldName);
        }

        if (self::FULL === $format) {
            return sprintf('CONCAT(%s)', str_replace(
                '%id%',
                sprintf("', %s.%s, '", $alias, $fieldName),
                (string)(new Expr())->literal($this->trans(self::TRANSLATION_KEY, [], $locale))
            ));
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

        $identifierFieldNames = $manager->getClassMetadata($className)->getIdentifierFieldNames();
        if (\count($identifierFieldNames) !== 1) {
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
        $getterName = 'get' . $this->inflector->classify($fieldName);
        if (EntityPropertyInfo::methodExists($entity, $getterName)) {
            return $entity->{$getterName}();
        }

        return $entity->{$fieldName} ?? null;
    }

    private function trans(string $key, array $params, string|Localization|null $locale): string
    {
        if ($locale instanceof Localization) {
            $locale = $locale->getLanguageCode();
        }

        return $this->translator->trans($key, $params, null, $locale);
    }
}
