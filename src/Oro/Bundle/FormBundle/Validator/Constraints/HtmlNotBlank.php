<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * HtmlNotBlank constraint
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
class HtmlNotBlank extends NotBlank
{
}
