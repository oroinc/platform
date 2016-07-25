<?php

namespace Oro\Bundle\LocaleBundle\Model;

use Oro\Bundle\LocaleBundle\Entity\FallbackAwareInterface;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;

class ExtendFallback implements FallbackAwareInterface
{
    use FallbackTrait;
}
