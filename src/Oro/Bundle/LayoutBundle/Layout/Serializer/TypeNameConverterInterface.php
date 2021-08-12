<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

/**
 * This interface should be implemented bу normalizers that can convert type name to short type name and vise versa.
 */
interface TypeNameConverterInterface
{
    /**
     * Gets a short form of the given type.
     */
    public function getShortTypeName(string $type): ?string;

    /**
     * Gets a type by its short form.
     */
    public function getTypeName(string $shortType): ?string;
}
