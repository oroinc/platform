<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures;

use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;

interface FirstLastNameAwareInterface extends FirstNameInterface, LastNameInterface
{
}
