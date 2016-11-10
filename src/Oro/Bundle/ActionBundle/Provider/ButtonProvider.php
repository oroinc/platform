<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;

class ButtonProvider
{
    /** @var ButtonProviderExtensionInterface[] */
    protected $extensions;

    /**
     * @param ButtonProviderExtensionInterface $extension
     */
    public function addExtension(ButtonProviderExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @param ButtonSearchContext $searchContext
     * @return ButtonInterface[]
     */
    public function findAll(ButtonSearchContext $searchContext)
    {
        $buttons = [];
        foreach ($this->extensions as $extension) {
            $buttons = array_merge($buttons, $extension->find($searchContext));
        }

        usort(
            $buttons,
            function (ButtonInterface $a, ButtonInterface $b) {
                return $a->getOrder() - $b->getOrder();
            }
        );

        return $buttons;
    }
}
