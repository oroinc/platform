<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData\CustomizeFormDataProcessorTestCase;
use Oro\Bundle\CurrencyBundle\Api\Processor\SetCurrency;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Stub\CurrencyAwareStub;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SetCurrencyTest extends CustomizeFormDataProcessorTestCase
{
    private const CURRENCY_FIELD_NAME = 'currency';

    private LocaleSettings&MockObject $localeSettings;
    private SetCurrency $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->processor = new SetCurrency(
            PropertyAccess::createPropertyAccessor(),
            $this->localeSettings,
            self::CURRENCY_FIELD_NAME
        );
    }

    /**
     * @return FormBuilderInterface
     */
    private function getFormBuilder()
    {
        return $this->createFormBuilder()->create(
            '',
            FormType::class,
            ['data_class' => CurrencyAwareStub::class]
        );
    }

    public function testProcessWhenFormHasSubmittedCurrencyField(): void
    {
        $entity = new CurrencyAwareStub();
        $currency = 'USD';

        $formBuilder = $this->getFormBuilder();
        $formBuilder->add(self::CURRENCY_FIELD_NAME, TextType::class);
        $form = $formBuilder->getForm();
        $form->setData($entity);
        $form->submit([self::CURRENCY_FIELD_NAME => $currency], false);
        self::assertTrue($form->isSynchronized());

        $this->localeSettings->expects(self::never())
            ->method('getCurrency');

        $this->context->setForm($form);
        $this->context->setData($entity);
        $this->processor->process($this->context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasSubmittedCurrencyFieldButItIsNotMapped(): void
    {
        $entity = new CurrencyAwareStub();
        $currency = 'USD';

        $formBuilder = $this->getFormBuilder();
        $formBuilder->add(self::CURRENCY_FIELD_NAME, TextType::class, ['mapped' => false]);
        $form = $formBuilder->getForm();
        $form->setData($entity);
        $form->submit([self::CURRENCY_FIELD_NAME => $currency], false);
        self::assertTrue($form->isSynchronized());

        $this->localeSettings->expects(self::once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->context->setForm($form);
        $this->context->setData($entity);
        $this->processor->process($this->context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormDoesNotHaveCurrencyField(): void
    {
        $entity = new CurrencyAwareStub();
        $currency = 'USD';

        $formBuilder = $this->getFormBuilder();
        $form = $formBuilder->getForm();
        $form->setData($entity);

        $this->localeSettings->expects(self::once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->context->setForm($form);
        $this->context->setData($entity);
        $this->processor->process($this->context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedCurrencyField(): void
    {
        $entity = new CurrencyAwareStub();
        $currency = 'USD';

        $formBuilder = $this->getFormBuilder();
        $formBuilder->add(self::CURRENCY_FIELD_NAME, TextType::class);
        $form = $formBuilder->getForm();
        $form->setData($entity);

        $this->localeSettings->expects(self::once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->context->setForm($form);
        $this->context->setData($entity);
        $this->processor->process($this->context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedRenamedCurrencyField(): void
    {
        $entity = new CurrencyAwareStub();
        $currency = 'USD';

        $formBuilder = $this->getFormBuilder();
        $formBuilder->add('renamedCurrency', TextType::class, ['property_path' => self::CURRENCY_FIELD_NAME]);
        $form = $formBuilder->getForm();
        $form->setData($entity);

        $this->localeSettings->expects(self::once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->context->setForm($form);
        $this->context->setData($entity);
        $this->processor->process($this->context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedCurrencyFieldAndNoCurrencyInLocaleSettings(): void
    {
        $entity = new CurrencyAwareStub();

        $formBuilder = $this->getFormBuilder();
        $formBuilder->add(self::CURRENCY_FIELD_NAME, TextType::class);
        $form = $formBuilder->getForm();
        $form->setData($entity);

        $this->localeSettings->expects(self::once())
            ->method('getCurrency')
            ->willReturn(null);

        $this->context->setForm($form);
        $this->context->setData($entity);
        $this->processor->process($this->context);

        self::assertNull($entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedCurrencyFieldButCurrencyAlreadySetToEntity(): void
    {
        $entity = new CurrencyAwareStub();
        $currency = 'USD';
        $entity->setCurrency($currency);

        $formBuilder = $this->getFormBuilder();
        $formBuilder->add(self::CURRENCY_FIELD_NAME, TextType::class);
        $form = $formBuilder->getForm();
        $form->setData($entity);

        $this->localeSettings->expects(self::never())
            ->method('getCurrency');

        $this->context->setForm($form);
        $this->context->setData($entity);
        $this->processor->process($this->context);

        self::assertSame($currency, $entity->getCurrency());
    }
}
