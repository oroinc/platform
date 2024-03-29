<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Model;

use Oro\Component\ChainProcessor\ParameterBag;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Represents a context that is used during email template rendering to store current context
 * parameters to make them available to inner email templates and TWIG instructions.
 */
class EmailTemplateRenderingContext extends ParameterBag implements ResetInterface
{
    public function fillFromArray(array $items): void
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function reset(): void
    {
        $this->clear();
    }
}
