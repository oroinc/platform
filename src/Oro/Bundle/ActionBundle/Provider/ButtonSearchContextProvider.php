<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;

class ButtonSearchContextProvider
{
    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param ContextHelper $contextHelper
     */
    public function __construct(ContextHelper $contextHelper)
    {
        $this->contextHelper = $contextHelper;
    }

    /**
     * @param array|null $context
     *
     * @return ButtonSearchContext
     */
    public function getButtonSearchContext(array $context = null)
    {
        return $this->buildFromContext($this->contextHelper->getContext($context));
    }

    /**
     * @param array $context
     *
     * @return ButtonSearchContext
     */
    protected function buildFromContext(array $context)
    {
        $buttonSearchContext = new ButtonSearchContext();

        return $buttonSearchContext
            ->setEntity($context[ContextHelper::ENTITY_CLASS_PARAM], $context[ContextHelper::ENTITY_ID_PARAM])
            ->setDatagrid($context[ContextHelper::DATAGRID_PARAM])
            ->setGroup($context[ContextHelper::GROUP_PARAM])
            ->setReferrer($context[ContextHelper::FROM_URL_PARAM])
            ->setRouteName($context[ContextHelper::ROUTE_PARAM]);
    }
}
