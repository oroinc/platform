<?php

namespace Oro\Bundle\CurrencyBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\SettablePriceAwareInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SetPriceByValueAndCurrency implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        $productItem = $context->getResult();

        if (!$requestData || false === ($productItem instanceof SettablePriceAwareInterface)) {
            return;
        }

        $context->setRequestData($this->processRequestData($productItem, $requestData));
    }

    /**
     * @param SettablePriceAwareInterface $priceSetterAwareItem
     * @param array                       $requestData
     *
     * @return array
     */
    protected function processRequestData(SettablePriceAwareInterface $priceSetterAwareItem, array $requestData)
    {
        $currency = null;
        $value = null;

        if ($priceSetterAwareItem->getPrice()) {
            $currency = $priceSetterAwareItem->getPrice()->getCurrency();
            $value = $priceSetterAwareItem->getPrice()->getValue();
        }

        if (array_key_exists('currency', $requestData)) {
            $currency = $requestData['currency'];
        }

        if (array_key_exists('value', $requestData)) {
            $value = $requestData['value'];
        }

        if (null === $currency || null === $value) {
            return $requestData;
        }

        $priceSetterAwareItem->setPrice(Price::create($value, $currency));

        $requestData['currency'] = $currency;
        $requestData['value'] = $value;

        return $requestData;
    }
}
