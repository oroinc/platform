<?php

namespace Oro\Bundle\ActionBundle\Extension;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;

/**
 * Defines the contract for button provider extensions that find and validate buttons.
 *
 * Implementations of this interface are responsible for finding buttons matching
 * a given search context and determining their availability based on permissions
 * and other restrictions.
 */
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
     * @param Collection|null $errors
     * @return bool
     * @throws UnsupportedButtonException
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        ?Collection $errors = null
    );

    /**
     * @param ButtonInterface $button
     * @return bool
     */
    public function supports(ButtonInterface $button);
}
