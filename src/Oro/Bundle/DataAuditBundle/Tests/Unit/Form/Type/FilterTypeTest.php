<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DataAuditBundle\Form\Type\FilterType as AuditFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilterTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormFactoryInterface
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->addTypeGuesser($this->createMock(ValidatorTypeGuesser::class))
            ->getFormFactory();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    public function testSubmit()
    {
        $formData = [
            'filter' => [
                'data' => 'data',
                'type' => 'type',
            ],
            'auditFilter' => [
                'data'       => 'auditData',
                'type'       => 'auditType',
                'columnName' => 'c',
            ],
        ];

        $form = $this->factory->create(AuditFilterType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
    }

    protected function getExtensions()
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $filterType = new FilterType($translator);

        return [
            new PreloadedExtension(
                [
                    $filterType->getName() => $filterType,
                ],
                []
            )
        ];
    }
}
