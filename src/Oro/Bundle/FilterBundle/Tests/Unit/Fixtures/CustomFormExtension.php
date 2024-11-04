<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Fixtures;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\FormTypeInterface;

class CustomFormExtension extends AbstractExtension
{
    /** @var FormTypeInterface[] */
    protected $initialTypes = [];

    public function __construct(array $initialTypes)
    {
        $this->initialTypes = $initialTypes;
    }

    #[\Override]
    protected function loadTypes(): array
    {
        return $this->initialTypes;
    }
}
