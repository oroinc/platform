<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider;

class TranslationPackagesProviderExtensionTest extends TranslationPackagesProviderExtensionTestAbstract
{
    /**
     * {@inheritdoc}
     */
    public function expectedPackagesDataProvider()
    {
        yield 'OroPlatform Package' => [
            'packageName' => 'Oro',
            'fileToLocate' => 'Oro/Bundle/TranslationBundle/OroTranslationBundle.php'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageName()
    {
        return 'platform';
    }
}
