<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Provider;

use Oro\Bundle\LocaleBundle\Provider\LocalizationScopeCriteriaProvider;
use Oro\Bundle\ScopeBundle\Tests\Functional\AbstractScopeProviderTestCase;

class LocalizationScopeCriteriaProviderTest extends AbstractScopeProviderTestCase
{
    public function testProviderRegistered()
    {
        self::assertProviderRegisteredWithScopeTypes(
            LocalizationScopeCriteriaProvider::LOCALIZATION,
            [
                'web_content'
            ]
        );
    }
}
