<?php

namespace Oro\Bundle\CurrencyBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\AbstractUpdateNestedModel;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Sets price based on "value" and "currency" fields if they are submitted.
 * It is expected that an entity for which this processor is used
 * has "getPrice()" and "setPrice(Price $price)" methods.
 */
class UpdatePriceByValueAndCurrency extends AbstractUpdateNestedModel
{
    public function __construct()
    {
        $this->modelPropertyPath = "price";
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var array $data */
        $data = $context->getData();
        $entity = $context->getForm()->getData();

        $value = null;
        $currency = null;

        $isValueSubmitted = false;
        $valueFieldName = $context->findFormFieldName('value');
        if (null !== $valueFieldName && array_key_exists($valueFieldName, $data)) {
            $isValueSubmitted = true;
            $value = $data[$valueFieldName];
        }

        $isCurrencySubmitted = false;
        $currencyFieldName = $context->findFormFieldName('currency');
        if (null !== $currencyFieldName && array_key_exists($currencyFieldName, $data)) {
            $isCurrencySubmitted = true;
            $currency = $data[$currencyFieldName];
        }

        if ($isValueSubmitted || $isCurrencySubmitted) {
            if (!$isValueSubmitted && null !== $entity->getPrice()) {
                $value = $entity->getPrice()->getValue();
            }
            if (!$isCurrencySubmitted && null !== $entity->getPrice()) {
                $currency = $entity->getPrice()->getCurrency();
            }
            $entity->setPrice(Price::create($value, $currency));
        }
    }
}
