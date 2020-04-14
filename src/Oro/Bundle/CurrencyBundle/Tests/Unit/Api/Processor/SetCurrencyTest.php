<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Api\Processor\SetCurrency;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Stub\CurrencyAwareStub;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SetCurrencyTest extends TypeTestCase
{
    private const CURRENCY_FIELD_NAME = 'currency';

    /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
    private $localeSettings;

    /** @var SetCurrency */
    private $processor;

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
        return $this->builder->create(
            null,
            FormType::class,
            ['data_class' => CurrencyAwareStub::class]
        );
    }

    public function testProcessWhenFormHasSubmittedCurrencyField()
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

        $context = new CustomizeFormDataContext();
        $context->setForm($form);
        $context->setData($entity);
        $this->processor->process($context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasSubmittedCurrencyFieldButItIsNotMapped()
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

        $context = new CustomizeFormDataContext();
        $context->setForm($form);
        $context->setData($entity);
        $this->processor->process($context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormDoesNotHaveCurrencyField()
    {
        $entity = new CurrencyAwareStub();
        $currency = 'USD';

        $formBuilder = $this->getFormBuilder();
        $form = $formBuilder->getForm();
        $form->setData($entity);

        $this->localeSettings->expects(self::once())
            ->method('getCurrency')
            ->willReturn($currency);

        $context = new CustomizeFormDataContext();
        $context->setForm($form);
        $context->setData($entity);
        $this->processor->process($context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedCurrencyField()
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

        $context = new CustomizeFormDataContext();
        $context->setForm($form);
        $context->setData($entity);
        $this->processor->process($context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedRenamedCurrencyField()
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

        $context = new CustomizeFormDataContext();
        $context->setForm($form);
        $context->setData($entity);
        $this->processor->process($context);

        self::assertSame($currency, $entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedCurrencyFieldAndNoCurrencyInLocaleSettings()
    {
        $entity = new CurrencyAwareStub();

        $formBuilder = $this->getFormBuilder();
        $formBuilder->add(self::CURRENCY_FIELD_NAME, TextType::class);
        $form = $formBuilder->getForm();
        $form->setData($entity);

        $this->localeSettings->expects(self::once())
            ->method('getCurrency')
            ->willReturn(null);

        $context = new CustomizeFormDataContext();
        $context->setForm($form);
        $context->setData($entity);
        $this->processor->process($context);

        self::assertNull($entity->getCurrency());
    }

    public function testProcessWhenFormHasNotSubmittedCurrencyFieldButCurrencyAlreadySetToEntity()
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

        $context = new CustomizeFormDataContext();
        $context->setForm($form);
        $context->setData($entity);
        $this->processor->process($context);

        self::assertSame($currency, $entity->getCurrency());
    }
}
