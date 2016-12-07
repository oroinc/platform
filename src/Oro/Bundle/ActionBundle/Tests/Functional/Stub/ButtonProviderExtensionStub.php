<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;

class ButtonProviderExtensionStub implements ButtonProviderExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        return [new ButtonStub()];
    }

    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    ) {
        return true;
    }

    public function supports(ButtonInterface $button)
    {
        return $button instanceof ButtonStub;
    }
}
