<?php

namespace Oro\Bundle\ActionBundle\Extension;

use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;

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
     * @return bool
     */
    public function isAvailable(ButtonInterface $button, ButtonSearchContext $buttonSearchContext);

    /**
     * @param ButtonInterface $button
     * @return bool
     */
    public function supports(ButtonInterface $button);
}
