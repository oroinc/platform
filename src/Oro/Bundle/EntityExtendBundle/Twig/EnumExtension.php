<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumExtension extends \Twig_Extension
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @var array
     *      key   => enum value entity class name
     *      value => array // values are sorted by priority
     *          key   => enum value id
     *          value => enum value name
     *
     */
    protected $localCache = [];

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
        foreach ($values as $id => $name) {
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

        return isset($values[$enumValueId])
            ? $values[$enumValueId]
            : $enumValueId;
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

        if (!isset($this->localCache[$enumValueEntityClassOrEnumCode])) {
            $items      = [];
            /** @var AbstractEnumValue[] $values */
            $values = $this->doctrine->getRepository($enumValueEntityClassOrEnumCode)->findAll();
            usort($values, function ($value1, $value2) {
                return $value1->getPriority() >= $value2->getPriority();
            });
            foreach ($values as $value) {
                $items[$value->getId()] = $value->getName();
            }
            $this->localCache[$enumValueEntityClassOrEnumCode] = $items;
        }

        return $this->localCache[$enumValueEntityClassOrEnumCode];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_enum';
    }
}
