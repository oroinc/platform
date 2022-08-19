<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Detects inconsistency of numeric enum attribute options and translations.
 *
 * - Numeric option id should be the same as id generated from option name (name=0.025 -> id=0025)
 * - Numeric option translation foreign_key should be the same
 *   as foreign_key generated from content (content=0.25 -> foreign_key=025)
 */
class AttributeValidator
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function validate(array $ids): array
    {
        $return = [];

        foreach ($ids as $id) {
            if ($result = $this->validateAttribute((int) $id)) {
                $return[] = $result;
            }
        }

        return $return;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateAttribute(int $id): ?array
    {
        /** @var FieldConfigModel $attribute */
        $attribute = $this->doctrine->getRepository(FieldConfigModel::class)->find($id);

        if ($attribute && $valuesClassName = $this->getValuesClassName($attribute)) {
            $result = [
                'class_name' => $attribute->getEntity()->getClassName(),
                'field_name' => $attribute->toArray('attribute')['field_name'],
                'outdated_options' => [],
                'outdated_translations' => [],
            ];

            /** @var AbstractEnumValue $value */
            foreach ($this->doctrine->getRepository($valuesClassName)->findAll() as $value) {
                if (!\is_numeric($value->getId())) {
                    continue;
                }

                if (!str_starts_with($value->getId(), ExtendHelper::buildEnumValueId($value->getName()))) {
                    $result['outdated_options'][] = [$value->getId(), $value->getName()];
                }
            }

            $translationRepository = $this->doctrine->getRepository(EnumValueTranslation::class);
            $translations = $translationRepository->findBy([
                'objectClass' => $valuesClassName,
                'field' => 'name',
            ]);

            /** @var EnumValueTranslation $translation */
            foreach ($translations as $translation) {
                $foreignKey = $translation->getForeignKey();
                $content = $translation->getContent();

                if (!\is_numeric($foreignKey)) {
                    continue;
                }

                if (!str_starts_with($foreignKey, ExtendHelper::buildEnumValueId($content))) {
                    $result['outdated_translations'][] = [$translation->getLocale(), $foreignKey, $content];
                }
            }

            if ($result['outdated_options'] || $result['outdated_translations']) {
                return $result;
            }
        }

        return null;
    }

    private function getValuesClassName(FieldConfigModel $attribute): ?string
    {
        if ('enum' === $attribute->getType()) {
            return $attribute->toArray('extend')['target_entity'] ?? null;
        }

        return null;
    }
}
