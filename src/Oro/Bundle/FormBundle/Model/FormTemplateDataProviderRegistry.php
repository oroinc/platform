<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Exception\UnknownProviderException;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

class FormTemplateDataProviderRegistry
{
    /** @var FormTemplateDataProviderInterface[] */
    private $formDataProviders = [];

    /**
     * @param string $alias
     * @return FormTemplateDataProviderInterface
     */
    public function get($alias)
    {
        if (!isset($this->formDataProviders[$alias])) {
            throw new UnknownProviderException($alias);
        }

        return $this->formDataProviders[$alias];
    }

    /**
     * @param FormTemplateDataProviderInterface $dataProvider
     * @param $alias
     */
    public function addProvider(FormTemplateDataProviderInterface $dataProvider, $alias)
    {
        $this->formDataProviders[$alias] = $dataProvider;
    }

    /**
     * @param $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->formDataProviders[$alias]);
    }
}
