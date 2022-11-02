<?php

namespace Oro\Bundle\ActionBundle\Provider;

/**
 * Interface for provider that returns routes needed for action.
 */
interface RouteProviderInterface
{
    public function getWidgetRoute(): string;

    public function getFormDialogRoute(): string;

    public function getFormPageRoute(): string;

    public function getExecutionRoute(): string;
}
