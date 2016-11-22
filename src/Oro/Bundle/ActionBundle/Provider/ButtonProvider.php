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
        if (0 === count($this->extensions)) {
            return [];
        }

        $buttonsData = [];
        foreach ($this->extensions as $extension) {
            $buttonsData[] = $extension->find($searchContext);
        }
        $buttons = call_user_func_array('array_merge', $buttonsData);

        usort(
            $buttons,
            function (ButtonInterface $a, ButtonInterface $b) {
                return $a->getOrder() - $b->getOrder();
            }
        );

        return $buttons;
    }

    /**
     * @param ButtonSearchContext $searchContext
     *
     * @return bool
     */
    public function hasButtons(ButtonSearchContext $searchContext)
    {
        foreach ($this->extensions as $extension) {
            if (count($extension->find($searchContext))) {
                return true;
            }
        }

        return false;
    }
}
