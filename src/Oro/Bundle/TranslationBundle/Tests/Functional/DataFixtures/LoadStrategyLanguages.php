<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

class LoadStrategyLanguages extends LoadLanguages
{
    public const LANGUAGES = [
        'lang1' => ['enabled' => false, 'user' => 'admin'],
        'lang2' => ['enabled' => false, 'user' => 'admin'],
        'lang3' => ['enabled' => false, 'user' => 'admin'],
        'lang4' => ['enabled' => false, 'user' => 'admin'],
    ];
}
