<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Exception\InvalidFieldTypeException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Normalized data based on entity fields config
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurableEntityNormalizer extends AbstractContextModeAwareNormalizer implements SerializerAwareInterface
{
    const FULL_MODE = 'full';
    const SHORT_MODE = 'short';

    /** @var SerializerInterface|NormalizerInterface|DenormalizerInterface */
    protected $serializer;

    /** @var FieldHelper */
    protected $fieldHelper;

    /** @var DenormalizerInterface */
    protected $scalarFieldDenormalizer;

    protected EnumOptionsProvider $enumOptionsProvider;

    protected DoctrineHelper $doctrineHelper;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;

        parent::__construct([self::FULL_MODE, self::SHORT_MODE], self::FULL_MODE);
    }

    public function setScalarFieldDenormalizer(DenormalizerInterface $scalarFieldDenormalizer): void
    {
        $this->scalarFieldDenormalizer = $scalarFieldDenormalizer;
    }

    public function setEnumOptionProvider(EnumOptionsProvider $enumOptionsProvider): void
    {
        $this->enumOptionsProvider = $enumOptionsProvider;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper): void
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $event = $this->dispatchDenormalize($data, $this->createObject($type), Events::BEFORE_DENORMALIZE_ENTITY);
        $result = $event->getObject();
        $fields = $this->fieldHelper->getEntityFields($type, EntityFieldProvider::OPTION_WITH_RELATIONS);

        foreach ($fields as $field) {
            if ($this->shouldSkipDenormalization($data, $field, $event)) {
                continue;
            }

            $fieldName = $field['name'];
            $value = $data[$fieldName];

            if ($data[$fieldName] !== null) {
                $fieldContext = $this->prepareFieldContextForDenormalization($context, $fieldName, $type, $field);
                $value = $this->processFieldValueForDenormalization($value, $field, $fieldContext, $format);
            }
            $this->setObjectValue($result, $fieldName, $value);
        }

        return $this->dispatchDenormalize($data, $result, Events::AFTER_DENORMALIZE_ENTITY)->getObject();
    }

    /**
     * Method can be overridden in normalizers for specific classes
     *
     * @param string $class
     *
     * @return object
     */
    protected function createObject(string $class)
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            return $reflection->newInstanceWithoutConstructor();
        }

        return $reflection->newInstance();
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && class_exists($type) && $this->fieldHelper->hasConfig($type);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $entityName = ClassUtils::getClass($object);
        $fields = $this->fieldHelper->getEntityFields($entityName, EntityFieldProvider::OPTION_WITH_RELATIONS);

        $result = [];

        $result = $this->dispatchNormalize($object, $result, $context, Events::BEFORE_NORMALIZE_ENTITY);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->shouldSkipNormalization($entityName, $fieldName, $field, $context)) {
                continue;
            }

            $fieldValue = $this->getObjectValue($object, $fieldName);
            $fieldContext = $this->prepareFieldContextForNormalization($context, $fieldName, $entityName, $field);

            if (is_object($fieldValue)) {
                $fieldValue = $this->serializer->normalize($fieldValue, $format, $fieldContext);
            } elseif (isset($field['type']) && ExtendHelper::isMultiEnumType($field['type']) && is_array($fieldValue)) {
                foreach ($fieldValue as &$item) {
                    $item = $this->serializer->normalize($item, $format, $fieldContext);
                }
            }

            $result[$fieldName] = $fieldValue;
        }

        return $this->dispatchNormalize($object, $result, $context, Events::AFTER_NORMALIZE_ENTITY);
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param array $context
     *
     * @return bool
     */
    protected function isFieldSkippedForNormalization($entityName, $fieldName, array $context)
    {
        // Do not normalize excluded fields
        $isExcluded = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded');

        // Do not normalize non identity fields for short mode
        $isNotIdentity = $this->getMode($context) === self::SHORT_MODE
            && !$this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity');

        return $isExcluded || $isNotIdentity;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (is_object($data)) {
            $dataClass = ClassUtils::getClass($data);

            return $this->fieldHelper->hasConfig($dataClass);
        }

        return false;
    }

    #[\Override]
    public function setSerializer(SerializerInterface $serializer): void
    {
        if (!$serializer instanceof NormalizerInterface ||
            !$serializer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Serializer must implement "%s" and "%s"',
                    NormalizerInterface::class,
                    DenormalizerInterface::class
                )
            );
        }
        $this->serializer = $serializer;
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasIdentityFields($entityName)
    {
        $fields = $this->fieldHelper->getEntityFields($entityName, EntityFieldProvider::OPTION_WITH_RELATIONS);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                return true;
            }
        }

        return false;
    }

    protected function dispatchDenormalize(array $data, object $result, string $eventName): DenormalizeEntityEvent
    {
        $event = new DenormalizeEntityEvent($result, $data);
        $this->dispatcher?->dispatch($event, $eventName);

        return $event;
    }

    /**
     * @param $object
     * @param $result
     * @param array $context
     * @param string $eventName
     *
     * @return array
     */
    protected function dispatchNormalize($object, $result, array $context, $eventName)
    {
        if ($this->dispatcher && $this->dispatcher->hasListeners($eventName)) {
            $event = new NormalizeEntityEvent($object, $result, $this->getMode($context) === static::FULL_MODE);
            $this->dispatcher->dispatch($event, $eventName);

            return $event->getResult();
        }

        return $result;
    }

    /**
     * @param object $object
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getObjectValue($object, $fieldName)
    {
        return $this->fieldHelper->getObjectValue($object, $fieldName);
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed $value
     */
    protected function setObjectValue($object, $fieldName, $value)
    {
        try {
            $this->fieldHelper->setObjectValue($object, $fieldName, $value);
        } catch (\TypeError $e) {
            // In reason of can't be possible to validate all types of entity fields and hiding original
            // errors, stack traces that shows system code structure we need to catch TypeError that thrown by
            // ReflectionProperty::setValue with incorrect type and throw another type of exception
            // for show correct error messages in importexport error log
            $entityClass = ClassUtils::getClass($object);
            $fieldHeader = $this->fieldHelper->getConfigValue($entityClass, $fieldName, 'header') ?: $fieldName;

            $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);
            $reflection = new EntityReflectionClass($entityClass);
            $shortName = $reflection->getShortName();

            if (isset($metadata->fieldMappings[$fieldName]['type'])) {
                $fieldType = $metadata->fieldMappings[$fieldName]['type'];
            } else {
                $fieldType = $reflection->getProperty($fieldName)->getType();
            }

            throw new InvalidFieldTypeException(
                sprintf(
                    '%s.%s: This value should contain only %s, "%s" given.',
                    $shortName,
                    $fieldHeader,
                    $fieldType,
                    $value
                )
            );
        }
    }

    private function shouldSkipNormalization($entityName, $fieldName, $field, $context): bool
    {
        return $this->isFieldSkippedForNormalization($entityName, $fieldName, $context)
            || $this->shouldSkipRelationField($entityName, $field);
    }

    private function shouldSkipDenormalization($data, $field, $event): bool
    {
        $fieldName = $field['name'];
        return !array_key_exists($fieldName, $data)
            || $event->isFieldSkipped($fieldName)
            // if data for object value is empty array we should not create empty object
            || ($this->fieldHelper->isSingleRelation($field) && [] === $data[$fieldName])
            || (ExtendHelper::isSingleEnumType($field['type']) && [] === $data[$fieldName]);
    }

    private function shouldSkipRelationField($entityName, $field): bool
    {
        $isFullMode = $this->fieldHelper->getConfigValue($entityName, $field['name'], 'full');

        // Do not export relation in short mode if it does not contain identity fields
        return !$isFullMode
            && isset($field['related_entity_name'])
            && $this->fieldHelper->hasConfig($field['related_entity_name'])
            && !$this->hasIdentityFields($field['related_entity_name']);
    }

    private function prepareFieldContextForNormalization($context, $fieldName, $entityName, $field): array
    {
        $fieldContext = $context;
        $fieldContext['fieldName'] = $fieldName;
        $fieldType = $field['type'] ?? '';

        if ($this->fieldHelper->isRelation($field) || ExtendHelper::isEnumerableType($fieldType)) {
            $fieldContext['mode'] = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full')
                ? self::FULL_MODE
                : self::SHORT_MODE;
        }

        if ($this->fieldHelper->isDateTimeField($field)) {
            $fieldContext['type'] = $fieldType;
        }

        return $fieldContext;
    }

    public function prepareFieldContextForDenormalization($context, $fieldName, $type, $field): array
    {
        $fieldContext = $context;
        $fieldContext['originalFieldName'] = $fieldContext['fieldName'] ?? $fieldName;
        $fieldContext['fieldName'] = $fieldName;
        $fieldContext['className'] = $type;

        if ($this->fieldHelper->isDateTimeField($field)) {
            $fieldContext['type'] = $field['type'];
        }

        return $fieldContext;
    }

    private function processFieldValueForDenormalization($value, $field, $fieldContext, $format): mixed
    {
        if ($this->fieldHelper->isRelation($field)) {
            return $this->processRelationField($value, $field, $fieldContext, $format);
        }

        if ($this->fieldHelper->isDateTimeField($field)) {
            return $this->processDateTimeField($value, $format, $fieldContext);
        }

        if (ExtendHelper::isEnumerableType($field['type'] ?? '')) {
            return $this->processEnumField($value, $field, $fieldContext);
        }

        return $value;
    }

    private function processRelationField($value, $field, $fieldContext, $format): mixed
    {
        $entityClass = $field['related_entity_name'];
        if ($this->fieldHelper->isMultipleRelation($field)) {
            $entityClass = 'ArrayCollection<' . $entityClass . '>';
        } else {
            if ([] === $value) {
                return null;
            }
        }

        return $this->serializer->denormalize($value, $entityClass, $format, $fieldContext);
    }

    private function processDateTimeField($value, $format, $fieldContext): mixed
    {
        return $this->serializer->denormalize($value, \DateTime::class, $format, $fieldContext);
    }

    private function processEnumField($value, array $field, array $fieldContext): mixed
    {
        $enumCode = $this->fieldHelper->getFieldConfig(
            'enum',
            $fieldContext['entityName'],
            $field['name']
        )->get('enum_code');
        if (ExtendHelper::isSingleEnumType($field['type'])) {
            $value = $this->mapEnumOption($enumCode, reset($value));
        } elseif (ExtendHelper::isMultiEnumType($field['type'])) {
            $value = array_map(fn ($item) =>  $this->mapEnumOption($enumCode, reset($item)), $value);
        }

        return $value;
    }

    private function mapEnumOption(string $enumCode, string $value): ?EnumOptionInterface
    {
        $enumOptions = $this->enumOptionsProvider->getEnumChoicesByCode($enumCode);
        if (empty($enumOptions)) {
            return null;
        }
        $optionClass = EnumOption::class;
        if (isset($enumOptions[$value])) {
            return $this->doctrineHelper->getEntityManager($optionClass)?->getReference(
                $optionClass,
                $enumOptions[$value]
            );
        }
        $enumOptionIdByValue = ExtendHelper::buildEnumOptionId($enumCode, $value);
        if (in_array($enumOptionIdByValue, $enumOptions, true)) {
            return $this->doctrineHelper->getEntityManager($optionClass)?->getReference(
                $optionClass,
                $enumOptionIdByValue
            );
        }
        try {
            if (ExtendHelper::extractEnumCode($value) === $enumCode) {
                return $this->doctrineHelper->getEntityManager($optionClass)?->getReference(
                    $optionClass,
                    $value
                );
            }
        } catch (\LogicException $exception) {
            // $value is not valid enum option id
        }


        // Cannot use getReference() here because it creates a Doctrine Proxy that throws
        // EntityNotFoundException when accessing any method on non-existent entities.
        return new EnumOption($enumCode, $value, ExtendHelper::buildEnumInternalId($value), 0, false);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
