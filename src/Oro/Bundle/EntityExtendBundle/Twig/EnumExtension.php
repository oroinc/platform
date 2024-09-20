<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
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

    public function getFilters(): array
    {
        return [
            new TwigFilter('sort_enum', [$this, 'sortEnum']),
            new TwigFilter('trans_enum', [$this, 'transEnum']),
        ];
    }

    /**
     * Sorts the given enum value identifiers according priorities specified for an enum values
     *
     * @param string|string[] $enumOptionIds The list of enum value identifiers.
     *                                      If this parameter is a string it is supposed that ids are
     *                                      delimited by comma (,).
     */
    public function sortEnum(mixed $enumOptionIds): array
    {
        $ids = $enumOptionIds;
        if ($ids === null) {
            $ids = [];
        } elseif (\is_string($ids)) {
            $ids = json_decode($ids);
        }
        if (empty($ids) || count($ids) === 1) {
            return $ids;
        }
        $enumCode = $this->getEnumCodeFromOptionId(reset($ids));
        if (null === $enumCode) {
            return [];
        }
        $ids = array_fill_keys($ids, true);
        $values = $this->getEnumOptions($enumCode);

        $result = [];
        foreach ($values as $id) {
            if (isset($ids[$id])) {
                $result[] = $id;
            }
        }

        return $result;
    }

    public function transEnum(?string $enumOptionId): ?string
    {
        if (null === $enumOptionId) {
            return null;
        }
        $enumCode = $this->getEnumCodeFromOptionId($enumOptionId);
        if (null === $enumCode) {
            return null;
        }
        $values = $this->getEnumOptions($enumCode);
        $label = array_search($enumOptionId, $values, true);

        return $label !== false ? $label : null;
    }

    /**
     *
     * @return array [enum value id => enum value name, ...] sorted by value priority
     */
    private function getEnumOptions(string $enumCode): array
    {
        return $this->getEnumOptionsProvider()->getEnumChoicesByCode($enumCode);
    }

    private function getEnumCodeFromOptionId(string $enumOptionId): ?string
    {
        try {
            return ExtendHelper::extractEnumCode($enumOptionId);
        } catch (\Throwable $exception) {
            // passed invalid enumOptionId
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'oro_entity_extend.enum_options_provider' => EnumOptionsProvider::class,
        ];
    }

    private function getEnumOptionsProvider(): EnumOptionsProvider
    {
        return $this->container->get('oro_entity_extend.enum_options_provider');
    }
}
