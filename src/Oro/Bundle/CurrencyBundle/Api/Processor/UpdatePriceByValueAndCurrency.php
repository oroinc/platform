<?php

namespace Oro\Bundle\CurrencyBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Sets price based on "value" and "currency" fields if they are submitted.
 * It is expected that an entity for which this processor is used
 * has "getPrice()" and "setPrice(Price $price)" methods.
 */
class UpdatePriceByValueAndCurrency implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_SUBMIT:
                $this->processPreSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_VALIDATE:
                $this->processPostValidate($context);
                break;
        }
    }

    /**
     * @param CustomizeFormDataContext $context
     */
    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var array $data */
        $data = $context->getData();
        $entity = $context->getForm()->getData();

        $value = null;
        $currency = null;

        $isValueSubmitted = false;
        $valueForm = $context->findFormField('value');
        if (null !== $valueForm && array_key_exists($valueForm->getName(), $data)) {
            $isValueSubmitted = true;
            $value = $data[$valueForm->getName()];
        }

        $isCurrencySubmitted = false;
        $currencyForm = $context->findFormField('currency');
        if (null !== $currencyForm && array_key_exists($currencyForm->getName(), $data)) {
            $isCurrencySubmitted = true;
            $currency = $data[$currencyForm->getName()];
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

    /**
     * @param CustomizeFormDataContext $context
     */
    private function processPostValidate(CustomizeFormDataContext $context): void
    {
        // fix property path for validation errors related to "price" field
        // it is required to return correct error source pointer in API response
        $errors = $context->getForm()->getErrors();
        foreach ($errors as $error) {
            $cause = $error->getCause();
            if ($cause instanceof ConstraintViolation
                && 0 === strpos($cause->getPropertyPath(), 'data.price.')
            ) {
                $property = ReflectionUtil::getProperty(new \ReflectionClass($cause), 'propertyPath');
                if (null !== $property) {
                    $property->setAccessible(true);
                    $property->setValue($cause, str_replace('.price.', '.', $cause->getPropertyPath()));
                }
            }
        }
    }
}
