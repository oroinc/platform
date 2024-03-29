<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\ScreenshotGenerator;

/**
 * Allows making screenshots in Behat contexts
 */
trait ScreenshotTrait
{
    private ScreenshotGenerator $screenshotGenerator;

    public function setScreenshotGenerator(ScreenshotGenerator $screenshotGenerator)
    {
        $this->screenshotGenerator = $screenshotGenerator;
    }

    private function takeScreenshot(): void
    {
        $urls = $this->screenshotGenerator->take();
        foreach ($urls as $url) {
            echo 'Screenshot: ' . $url;
        }
    }
}
