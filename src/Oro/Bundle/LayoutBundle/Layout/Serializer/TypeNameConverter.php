<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

/**
 * Provides a functionality to convert type name to short type name and vise versa.
 */
class TypeNameConverter
{
    /** @var TypeNameConverterInterface[] */
    private array $converters;
    private array $shortTypeMap = [];
    private array $typeMap = [];

    /**
     * @param TypeNameConverterInterface[] $converters
     */
    public function __construct(array $converters)
    {
        $this->converters = $converters;
    }

    /**
     * Gets a short form of the given type.
     */
    public function getShortTypeName(string $type): ?string
    {
        if (\array_key_exists($type, $this->shortTypeMap)) {
            return $this->shortTypeMap[$type];
        }

        $shortType = null;
        foreach ($this->converters as $converter) {
            $shortType = $converter->getShortTypeName($type);
            if (null !== $shortType) {
                break;
            }
        }
        $this->shortTypeMap[$type] = $shortType;

        return $shortType;
    }

    /**
     * Gets a type by its short form.
     */
    public function getTypeName(string $shortType): ?string
    {
        if (\array_key_exists($shortType, $this->typeMap)) {
            return $this->typeMap[$shortType];
        }

        $type = null;
        foreach ($this->converters as $converter) {
            $type = $converter->getTypeName($shortType);
            if (null !== $type) {
                break;
            }
        }
        $this->typeMap[$shortType] = $type;

        return $type;
    }
}
