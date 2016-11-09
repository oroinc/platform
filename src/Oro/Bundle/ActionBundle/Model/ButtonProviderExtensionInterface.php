<?php

namespace Oro\Bundle\ActionBundle\Model;

interface ButtonProviderExtensionInterface
{
    /**
     * @param ButtonSearchContext $buttonSearchContext
     * @return ButtonInterface[]
     */
    public function find(ButtonSearchContext $buttonSearchContext);
}
