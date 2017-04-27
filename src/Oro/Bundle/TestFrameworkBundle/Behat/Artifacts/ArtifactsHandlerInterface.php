<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

interface ArtifactsHandlerInterface
{
    /**
     * @param string $file
     * @return string Url to uploaded artifact
     */
    public function save($file);

    /**
     * @return string
     */
    public static function getConfigKey();
}
