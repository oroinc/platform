<?php

namespace Oro\Bundle\EntityExtendBundle\ImportExport\Serializer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Normalizer for enum entities.
 */
class EnumNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    protected FieldHelper $fieldHelper;

    protected EnumOptionsProvider $enumOptionsProvider;

    public function __construct(FieldHelper $fieldHelper, EnumOptionsProvider $enumOptionsProvider)
    {
        $this->fieldHelper = $fieldHelper;
        $this->enumOptionsProvider = $enumOptionsProvider;
    }

    /**
     * @param EnumOptionInterface $object
     *
     */
    #[\Override]
    public function normalize($object, string $format = null, array $context = []): ?array
    {
        if (!$object instanceof EnumOptionInterface) {
            return null;
        }

        if (!empty($context['mode']) && $context['mode'] === 'short') {
            return $this->getShortData($object, $context);
        }

        return [
            'id' => $object->getId(),
            'enumCode' => $object->getEnumCode(),
            'name' => $object->getName(),
            'internalId' => $object->getInternalId(),
            'priority' => (int)$object->getPriority(),
            'is_default' => (bool)$object->isDefault(),
        ];
    }

    #[\Override]
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $reflection = new \ReflectionClass($type);

        $choices = $this->enumOptionsProvider->getEnumChoicesByCode($type);
        // isset is used instead of empty as $data['id'] could be "0"
        $id = isset($data['id']) && '' !== $data['id'] ? $data['id'] : $choices[$data['name'] ?? ''] ?? '';

        $args = [
            'enumCode' => $data['enumCode'] ?? '',
            'name' => $data['name'] ?? '',
            'internalId' => $data['internalId'] ?? explode('.', $id)[1] ?? '',
            'priority' => empty($data['priority']) ? 0 : $data['priority'],
            'default' => !empty($data['default']),
        ];

        return $reflection->newInstanceArgs($args);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, EnumOptionInterface::class, true);
    }

    #[\Override]
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof EnumOptionInterface;
    }

    protected function getShortData(EnumOptionInterface $object, $context): array
    {
        $owner = isset($context['entityName'])
            ? $this->fieldHelper->getExtendConfigOwner($context['entityName'], $context['fieldName'])
            : ClassUtils::getClass($object);

        return $owner === ExtendScope::OWNER_CUSTOM
            ? ['name' => (string)$object]
            : ['id' => $object->getInternalId()];
    }
}
