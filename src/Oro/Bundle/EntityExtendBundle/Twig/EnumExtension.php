<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to sort and translate enum values:
 *   - sort_enum - sorts the given enum value identifiers according to the priorities specified for this enum.
 *   - trans_enum - translates the given enum value.
 */
class EnumExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('sort_enum', [$this, 'sortEnum']),
            new TwigFilter('trans_enum', [$this, 'transEnum']),
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
        } elseif (\is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || count($ids) === 1) {
            return $ids;
        }

        $ids = array_fill_keys($ids, true);
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
        $label = array_search($enumValueId, $values, true);

        return $label !== false ? $label : $enumValueId;
    }

    /**
     * @param string $enumValueEntityClassOrEnumCode
     *
     * @return array [enum value id => enum value name, ...] sorted by value priority
     */
    private function getEnumValues($enumValueEntityClassOrEnumCode)
    {
        if (!str_contains($enumValueEntityClassOrEnumCode, '\\')) {
            $enumValueEntityClassOrEnumCode = ExtendHelper::buildEnumValueClassName($enumValueEntityClassOrEnumCode);
        }

        return $this->getEnumValueProvider()->getEnumChoices($enumValueEntityClassOrEnumCode);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_entity_extend.enum_value_provider' => EnumValueProvider::class,
        ];
    }

    private function getEnumValueProvider(): EnumValueProvider
    {
        return $this->container->get('oro_entity_extend.enum_value_provider');
    }
}
