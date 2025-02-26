<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Healer\Extension;

use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\CallResult;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Healer\HealerInterface;

/**
 * Healer extension that attempts to fix a failed step by reloading the current page.
 */
class ReloadPageHealer implements HealerInterface
{
    protected const array SUPPORTED_STEPS = [
        'assertPageContainsText'
    ];

    public function __construct(protected OroElementFactory $elementFactory)
    {
    }

    public function supports(Call $call): bool
    {
        if (!isset($call->getBoundCallable()[1])) {
            return false;
        }
        if (in_array($call->getBoundCallable()[1], self::SUPPORTED_STEPS, true)) {
            return true;
        }

        return false;
    }

    public function process(Call &$call, CallResult $failedCall): bool
    {
        try {
            $page = $this->elementFactory->getPage();
            $page->getSession()->reload();
        } catch (\Throwable $exception) {
            // failed attempt to fix the step
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return 'Reloading current page';
    }

    public function fallInAnyResult(): bool
    {
        return false;
    }
}
