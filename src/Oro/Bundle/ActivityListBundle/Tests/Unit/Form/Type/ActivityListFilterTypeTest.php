<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter;
use Oro\Bundle\ActivityListBundle\Form\Type\ActivityListFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ActivityListFilterTypeTest extends TypeTestCase
{
    public function setUp()
    {
        parent::setUp();

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->will($this->returnValue(new ConstraintViolationList()));

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(
                new FormTypeValidatorExtension(
                    $validator
                )
            )
            ->addTypeGuesser(
                $this->getMockBuilder(
                    'Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser'
                )
                    ->disableOriginalConstructor()
                    ->getMock()
            )
            ->getFormFactory();

        $this->dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    public function testSubmitValidData()
    {
        $formData = [
            'filter' => 'filter',
            'entityClassName' => 'entity',
            'activityType' => [
                'value' => ['val'],
            ],
            'filterType' => ActivityListFilter::TYPE_HAS_ACTIVITY,
        ];

        $form = $this->factory->create(ActivityListFilterType::class);
        $form->submit($formData);
        
        $this->assertTrue($form->isValid());
    }

    protected function getExtensions()
    {
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

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
