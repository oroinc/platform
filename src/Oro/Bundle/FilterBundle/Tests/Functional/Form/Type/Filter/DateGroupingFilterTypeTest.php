<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Form\Type\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateGroupingFilterType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DateGroupingFilterTypeTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider validDataProvider
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testFormSubmitValidData(array $submittedData, array $expectedData)
    {
        $form = $this->getFormFactory()->create(
            DateGroupingFilterType::NAME,
            null,
            [
                'csrf_protection' => false,
            ]
        );

        $form->submit($submittedData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function validDataProvider()
    {
        return [
            'type_day' => [
                [
                    'value' => DateGroupingFilterType::TYPE_DAY,
                ],
                [
                    'value' => DateGroupingFilterType::TYPE_DAY,
                    'type' => null,
                ]
            ],
            'type_month' => [
                [
                    'value' => DateGroupingFilterType::TYPE_MONTH,
                ],
                [
                    'value' => DateGroupingFilterType::TYPE_MONTH,
                    'type' => null,
                ]
            ],
            'type_quarter' => [
                [
                    'value' => DateGroupingFilterType::TYPE_QUARTER,
                ],
                [
                    'value' => DateGroupingFilterType::TYPE_QUARTER,
                    'type' => null,
                ]
            ],
            'type_year' => [
                [
                    'value' => DateGroupingFilterType::TYPE_YEAR,
                ],
                [
                    'value' => DateGroupingFilterType::TYPE_YEAR,
                    'type' => null,
                ]
            ],
        ];
    }

    /**
     * @return FormFactoryInterface
     */
    protected function getFormFactory()
    {
        return $this->getContainer()->get('form.factory');
    }
}
