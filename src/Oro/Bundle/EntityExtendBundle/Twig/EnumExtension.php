<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Twig extension for filters:
 *  1) sort_enum - Sorts the given enum value identifiers according priorities specified for an enum values
 *  2) trans_enum - Translates the given enum value
 */
class EnumExtension extends \Twig_Extension
{
    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /**
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(EnumValueProvider $enumValueProvider)
    {
        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('sort_enum', [$this, 'sortEnum']),
            new \Twig_SimpleFilter('trans_enum', [$this, 'transEnum']),
        ];
    }

    /**
     * Sorts the given enum value identifiers according priorities specified for an enum values
     *
     * @param string|string[] $enumValueIds The list of enum value identifiers.
     *                                      If this parameter is a string it is supposed that ids are
     *                                      delimited by comma (,).
     * @param string          $enumValueEntityClassOrEnumCode
     *
     * @return string[]
     */
    public function sortEnum($enumValueIds, $enumValueEntityClassOrEnumCode)
    {
        $ids = $enumValueIds;
        if ($ids === null) {
            $ids = [];
        } elseif (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || count($ids) === 1) {
            return $ids;
        }

        $ids    = array_fill_keys($ids, true);
        $values = $this->getEnumValues($enumValueEntityClassOrEnumCode);

        $result = [];
        foreach ($values as $name => $id) {
            if (isset($ids[$id])) {
                $result[] = $id;
            }
        }

        return $result;
    }

    /**
     * Translates the given enum value
     *
     * @param string $enumValueId
     * @param string $enumValueEntityClassOrEnumCode
     *
     * @return string
     */
    public function transEnum($enumValueId, $enumValueEntityClassOrEnumCode)
    {
        $values = $this->getEnumValues($enumValueEntityClassOrEnumCode);
        $label = array_search($enumValueId, $values);

        return $label !== false ? $label : $enumValueId;
    }

    /**
     * @param $enumValueEntityClassOrEnumCode
     *
     * @return array sorted by value priority
     *      key   => enum value id
     *      value => enum value name
     */
    protected function getEnumValues($enumValueEntityClassOrEnumCode)
    {
        if (strpos($enumValueEntityClassOrEnumCode, '\\') === false) {
            $enumValueEntityClassOrEnumCode = ExtendHelper::buildEnumValueClassName($enumValueEntityClassOrEnumCode);
        }

        return $this->enumValueProvider->getEnumChoices($enumValueEntityClassOrEnumCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_enum';
    }
}
