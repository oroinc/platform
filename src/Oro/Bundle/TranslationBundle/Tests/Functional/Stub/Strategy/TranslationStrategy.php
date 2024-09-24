<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;

class TranslationStrategy implements TranslationStrategyInterface
{
    private string $name;
    private array $locales;

    public function __construct(string $name, array $locales)
    {
        $this->name = $name;
        $this->locales = $locales;
    }

    #[\Override]
    public function isApplicable()
    {
        return true;
    }

    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    #[\Override]
    public function getLocaleFallbacks()
    {
        return $this->locales;
    }
}
