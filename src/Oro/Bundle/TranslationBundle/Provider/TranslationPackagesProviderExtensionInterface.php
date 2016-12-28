<?php

namespace Oro\Bundle\TranslationBundle\Provider;

interface TranslationPackagesProviderExtensionInterface
{
    /**
     * @return array|string[]
     */
    public function getPackageNames();
}
