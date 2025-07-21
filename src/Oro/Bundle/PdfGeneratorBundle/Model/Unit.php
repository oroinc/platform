<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Model;

/**
 * Represents a size unit.
 */
enum Unit: string
{
    case inches = 'in';
    case points = 'pt';
    case pixels = 'px';
    case millimeters = 'mm';
    case centimeters = 'cm';
    case picas = 'pc';
}
