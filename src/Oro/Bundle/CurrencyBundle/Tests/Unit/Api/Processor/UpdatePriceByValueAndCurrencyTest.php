<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData\CustomizeFormDataProcessorTestCase;
use Oro\Bundle\CurrencyBundle\Api\Processor\UpdatePriceByValueAndCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Stub\PriceAwareEntityStub;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

class UpdatePriceByValueAndCurrencyTest extends CustomizeFormDataProcessorTestCase
{
    /** @var UpdatePriceByValueAndCurrency */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new UpdatePriceByValueAndCurrency();
    }

    private function getEntity(Price $price = null): PriceAwareEntityStub
    {
        $entity = new PriceAwareEntityStub();
        $entity->setPrice($price);

        return $entity;
    }

    private function getForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => PriceAwareEntityStub::class])
            ->add('currency', TextType::class, ['mapped' => false])
            ->add('value', TextType::class, ['mapped' => false])
            ->getForm();
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testPreSubmit(
        array $data,
        PriceAwareEntityStub $entity,
        PriceAwareEntityStub $expectedEntity
    ) {
        $form = $this->getForm();
        $form->setData($entity);

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertEquals($expectedEntity, $entity);
    }

    public function requestDataProvider(): array
    {
        return [
            'empty request'             => [
                'data'           => [],
                'entity'         => $this->getEntity(),
                'expectedEntity' => $this->getEntity()
            ],
            'empty currency'            => [
                'data'           => ['value' => 10],
                'entity'         => $this->getEntity(),
                'expectedEntity' => $this->getEntity(Price::create(10, null))
            ],
            'empty value'               => [
                'data'           => ['currency' => 'USD'],
                'entity'         => $this->getEntity(),
                'expectedEntity' => $this->getEntity(Price::create(null, 'USD'))
            ],
            'empty currency, has price' => [
                'data'           => ['value' => 10],
                'entity'         => $this->getEntity(Price::create(20, 'USD')),
                'expectedEntity' => $this->getEntity(Price::create(10, 'USD'))
            ],
            'empty value, has price'    => [
                'data'           => ['currency' => 'USD'],
                'entity'         => $this->getEntity(Price::create(10, 'EUR')),
                'expectedEntity' => $this->getEntity(Price::create(10, 'USD'))
            ],
            'value & currency exist'    => [
                'data'           => ['currency' => 'USD', 'value' => 10],
                'entity'         => $this->getEntity(),
                'expectedEntity' => $this->getEntity(Price::create(10, 'USD'))
            ]
        ];
    }

    public function testPostValidate()
    {
        $form = $this->getForm();
        $form->addError(new FormError(
            'some error 1',
            null,
            [],
            null,
            new ConstraintViolation('', '', [], null, 'data.price.value', '')
        ));
        $form->addError(new FormError(
            'some error 2',
            null,
            [],
            null,
            new ConstraintViolation('', '', [], null, 'data.other', '')
        ));

        $this->context->setEvent(CustomizeFormDataContext::EVENT_POST_VALIDATE);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertEquals(
            ['data.value', 'data.other'],
            array_map(
                function ($error) {
                    return $error->getCause()->getPropertyPath();
                },
                iterator_to_array($form->getErrors())
            )
        );
    }
}
