<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter;
use Oro\Bundle\ActivityListBundle\Form\Type\ActivityListFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;

class ActivityListFilterTypeTest extends TypeTestCase
{
    public function setUp()
    {
        parent::setUp();

        $validator = $this->getMock('Symfony\Component\Validator\ValidatorInterface');
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

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
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

        $type = new ActivityListFilterType();
        $form = $this->factory->create($type);
        $form->submit($formData);
        
        $this->assertTrue($form->isValid());
    }

    protected function getExtensions()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

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
