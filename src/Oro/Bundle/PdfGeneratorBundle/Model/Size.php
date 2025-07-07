<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Model;

use Stringable;

/**
 * Represents a size, i.e. a page size, margins, etc.
 */
class Size implements Stringable
{
    public function __construct(private float $size, private Unit $unit = Unit::inches)
    {
    }

    /**
     * @param Size|string|int|float $size Must respect format %f%s like '12in' or '12.2px' or '12'.
     *
     * @return self
     *
     * @throws \InvalidArgumentException if $raw does not follow correct format
     */
    public static function create(Size|string|int|float $size): self
    {
        if ($size instanceof self) {
            return $size;
        }

        $result = sscanf((string)$size, '%f%s', $value, $unit);

        if ($result < 1 || !is_numeric($value)) {
            throw new \InvalidArgumentException(\sprintf('Unexpected value "%s", expected format is "%%f%%s"', $size));
        }

        if ($unit) {
            $unitEnum = Unit::tryFrom((string)$unit);
            if ($unitEnum === null) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Unexpected unit "%s", available units are "%s"',
                        $unit,
                        implode('", "', array_column(Unit::cases(), 'value'))
                    )
                );
            }
        }

        return new self((float)$size, $unitEnum ?? Unit::inches);
    }

    public function getSize(): float
    {
        return $this->size;
    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    public function __toString(): string
    {
        return $this->size . $this->unit->value;
    }
}
