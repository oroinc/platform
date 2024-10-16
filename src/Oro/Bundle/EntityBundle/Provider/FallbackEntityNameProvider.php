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
    private const TRANSLATION_KEY = 'oro.entity.item';

    private ManagerRegistry $doctrine;
    private TranslatorInterface $translator;
    private Inflector $inflector;

    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator, Inflector $inflector)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->inflector = $inflector;
    }

    #[\Override]
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

    #[\Override]
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

    private function getFieldName(string $className): ?string
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

    private function getFieldValue(object $entity, string $fieldName): mixed
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
