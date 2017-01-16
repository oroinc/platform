<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

class LoadStrategyLanguages extends LoadLanguages
{
    /**
     * @var array
     */
    protected $languages = [
        'lang1' => ['enabled' => false, 'user' => 'admin'],
        'lang2' => ['enabled' => false, 'user' => 'admin'],
        'lang3' => ['enabled' => false, 'user' => 'admin'],
        'lang4' => ['enabled' => false, 'user' => 'admin'],
    ];
}
