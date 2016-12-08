<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;

class ButtonProviderExtensionStub implements ButtonProviderExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        return [new ButtonStub()];
    }

    /**
     * @param ButtonInterface $button
     * @param ButtonSearchContext $buttonSearchContext
     * @param Collection $errors
     *
     * @return bool
     * @throws UnsupportedButtonException
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    ) {
        return true;
    }

    /**
     * @param ButtonInterface $button
     *
     * @return bool
     */
    public function supports(ButtonInterface $button)
    {
        return $button instanceof ButtonStub;
    }
}
