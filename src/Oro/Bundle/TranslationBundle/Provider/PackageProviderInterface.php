<?php

namespace Oro\Bundle\TranslationBundle\Provider;

interface PackageProviderInterface
{
    /**
     * @return array
     */
    public function getInstalledPackages();
}
