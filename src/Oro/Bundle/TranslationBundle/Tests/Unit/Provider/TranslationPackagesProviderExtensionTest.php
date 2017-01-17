<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\OroTranslationBundle;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtension;

class TranslationPackagesProviderExtensionTest extends TranslationPackagesProviderExtensionTestAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function createExtension()
    {
        return new TranslationPackagesProviderExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackagesName()
    {
        return [TranslationPackagesProviderExtension::PACKAGE_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function packagePathProvider()
    {
        return [
            [
                'path' => str_replace('\\', '/', sprintf('%s.php', OroTranslationBundle::class))
            ]
        ];
    }
}
