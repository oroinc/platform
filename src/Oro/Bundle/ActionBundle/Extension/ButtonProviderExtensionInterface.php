<?php

namespace Oro\Bundle\ActionBundle\Extension;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;

interface ButtonProviderExtensionInterface
{
    /**
     * @param ButtonSearchContext $buttonSearchContext
     * @return ButtonInterface[]
     */
    public function find(ButtonSearchContext $buttonSearchContext);

    /**
     * @param ButtonInterface $button
     * @param ButtonSearchContext $buttonSearchContext
     * @param Collection $errors
     * @return bool
     * @throws UnsupportedButtonException
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    );

    /**
     * @param ButtonInterface $button
     * @return bool
     */
    public function supports(ButtonInterface $button);
}
