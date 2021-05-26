<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Stub;

use Symfony\Component\Form\FormTypeExtensionInterface;

interface StubFormTypeExtensionInterface extends FormTypeExtensionInterface
{
    public function getExtendedTypes(): iterable;
}
