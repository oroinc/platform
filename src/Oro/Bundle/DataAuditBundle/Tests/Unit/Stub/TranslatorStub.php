<?php

declare(strict_types=1);

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Stub;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorStub implements TranslatorInterface, LocaleAwareInterface
{
    public array $catalogue = [];
    private string $locale = 'en';

    #[\Override]
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->catalogue[$locale ?? $this->locale][$id] ?? $id;
    }

    #[\Override]
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    #[\Override]
    public function getLocale(): string
    {
        return $this->locale;
    }
}
