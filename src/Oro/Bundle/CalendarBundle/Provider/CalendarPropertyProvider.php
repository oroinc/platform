<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class CalendarPropertyProvider
{
    const CALENDAR_PROPERTY_CLASS = 'Oro\Bundle\CalendarBundle\Entity\CalendarProperty';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var CalendarProviderInterface[] */
    protected $providers = [];

    /** @var string[] */
    private $fields;

    /** @var ClassMetadata */
    private $metadata;

    /** @var array */
    private $computedDefaultValues = [];

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ConfigManager   $configManager
     * @param FieldTypeHelper $fieldTypeHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->doctrineHelper  = $doctrineHelper;
        $this->configManager   = $configManager;
        $this->fieldTypeHelper = $fieldTypeHelper;
    }

    /**
     * @param int $calendarId
     *
     * @return array
     */
    public function getItems($calendarId)
    {
        $selectItems = [];
        $intCasts    = [];
        $metadata    = $this->getMetadata();
        $fields      = $this->getFields();
        foreach ($fields as $fieldName => $fieldType) {
            $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($fieldType);
            if ($this->fieldTypeHelper->isRelation($underlyingFieldType)) {
                $selectItems[] = sprintf('IDENTITY(o.%1$s) AS %1$s', $fieldName);
                if ($metadata->hasAssociation($fieldName)) {
                    $assocType = $this->doctrineHelper->getSingleEntityIdentifierFieldType(
                        $metadata->getAssociationTargetClass($fieldName),
                        false
                    );
                    if ($assocType === 'integer') {
                        $intCasts[] = $fieldName;
                    }
                }
            } else {
                $selectItems[] = 'o.' . $fieldName;
            }
        }

        $repo = $this->doctrineHelper->getEntityRepository(self::CALENDAR_PROPERTY_CLASS);
        $qb   = $repo->createQueryBuilder('o')
            ->select(implode(',', $selectItems))
            ->where('o.targetCalendar = :calendar_id')
            ->setParameter('calendar_id', $calendarId)
            ->orderBy('o.id');

        $result = $qb->getQuery()->getArrayResult();

        // normalize integer foreign keys due Doctrine IDENTITY function always returns a string
        if ($intCasts) {
            foreach ($result as &$item) {
                foreach ($intCasts as $fieldName) {
                    if (isset($item[$fieldName]) && is_string($item[$fieldName])) {
                        $item[$fieldName] = (int)$item[$fieldName];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int  $calendarId  The target calendar id
     * @param bool $subordinate Determines whether events from connected calendars should be included or not
     *
     * @return array of [calendarAlias, calendar, visible]
     */
    public function getItemsVisibility($calendarId, $subordinate)
    {
        $qb = $this->doctrineHelper
            ->getEntityRepository(self::CALENDAR_PROPERTY_CLASS)
            ->createQueryBuilder('o')
            ->select('o.calendarAlias, o.calendar, o.visible')
            ->where('o.targetCalendar = :calendar_id')
            ->setParameter('calendar_id', $calendarId);
        if (!$subordinate) {
            $qb
                ->andWhere('o.calendarAlias = :alias AND o.calendar = :calendar_id')
                ->setParameter('alias', Calendar::CALENDAR_ALIAS);
        }

        return $qb
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return string[]
     */
    public function getFields()
    {
        if (empty($this->fields)) {
            $this->fields = [];
            $fieldConfigs = $this->configManager->getConfigs('extend', self::CALENDAR_PROPERTY_CLASS);
            foreach ($fieldConfigs as $fieldConfig) {
                if (!ExtendHelper::isFieldAccessible($fieldConfig)) {
                    continue;
                }
                /** @var FieldConfigId $fieldId */
                $fieldId             = $fieldConfig->getId();
                $fieldType           = $fieldId->getFieldType();
                $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($fieldType);
                if (in_array($underlyingFieldType, RelationType::$toManyRelations, true)) {
                    // ignore to-many relations
                    continue;
                }

                $this->fields[$fieldId->getFieldName()] = $fieldType;
            }
        }

        return $this->fields;
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        $result = [];

        $metadata = $this->getMetadata();
        $fields   = $this->getFields();
        foreach ($fields as $fieldName => $fieldType) {
            $defaultValue = null;
            if ($metadata->hasField($fieldName)) {
                $mapping      = $metadata->getFieldMapping($fieldName);
                $defaultValue = isset($mapping['options']['default'])
                    ? $mapping['options']['default']
                    : null;
            } elseif ($fieldType === 'enum') {
                $defaultValue = [$this, 'getEnumDefaultValue'];
            }
            $result[$fieldName] = $defaultValue;
        }

        return $result;
    }

    /**
     * Gets a default option of an enum associated with the given field
     * This method must be public because it is used as a callback
     *
     * @param string $fieldName
     *
     * @return string|null
     */
    public function getEnumDefaultValue($fieldName)
    {
        if (isset($this->computedDefaultValues[$fieldName])
            || array_key_exists($fieldName, $this->computedDefaultValues)
        ) {
            return $this->computedDefaultValues[$fieldName];
        }

        $fieldConfig = $this->configManager->getConfig(
            new FieldConfigId('extend', self::CALENDAR_PROPERTY_CLASS, $fieldName, 'enum')
        );

        $repo = $this->doctrineHelper->getEntityRepository($fieldConfig->get('target_entity'));
        $data = $repo->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.default = true')
            ->getQuery()
            ->getArrayResult();

        if ($data) {
            $data = array_shift($data);
        }
        $result = $data ? $data['id'] : null;

        $this->computedDefaultValues[$fieldName] = $result;

        return $result;
    }

    /**
     * @return ClassMetadata
     */
    protected function getMetadata()
    {
        if (!$this->metadata) {
            $this->metadata = $this->doctrineHelper->getEntityMetadata(self::CALENDAR_PROPERTY_CLASS);
        }

        return $this->metadata;
    }
}
