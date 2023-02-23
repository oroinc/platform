<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Twig\Extension\AbstractExtension;

/**
 * This TWIG extension is used to check that TWIG is not loaded for API requests.
 */
class TestTwigExtension extends AbstractExtension
{
    public function __construct(TestTwigState $twigState)
    {
        if (!$twigState->isTwigEnabled()) {
            throw new RuntimeException(
                'TEST ASSERTION: The TWIG must not be loaded during execution of API requests. When your API resource'
                . ' requires TWIG you can disable this assertion via $this->enableTwig() in your test.'
            );
        }
    }
}
