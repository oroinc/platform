<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Symfony\Component\VarDumper\VarDumper;

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
        VarDumper::dump([__FILE__, __LINE__, $buttonsData]);

        $buttons = call_user_func_array('array_merge', $buttonsData);

        usort(
            $buttons,
            function (ButtonInterface $a, ButtonInterface $b) {
                return $a->getOrder() - $b->getOrder();
            }
        );

        return $buttons;
    }
}
