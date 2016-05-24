<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Form\Type\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DateRangeFilterTypeTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->initClient();
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testFormSubmitValidData(array $submittedData, array $expectedData)
    {
        $form = $this->getFormFactory()->create(DateRangeFilterType::NAME, null, [
            'csrf_protection' => false,
        ]);

        $form->submit($submittedData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function validDataProvider()
    {
        return [
            'edge days' => [
                [
                    'part'  => DateModifierInterface::PART_DAY,
                    'value' => ['start' => 1, 'end' => 31],
                ],
                [
                    'part'  => DateModifierInterface::PART_DAY,
                    'value' => ['start' => 1, 'end' => 31],
                    'type'  => null,
                ],
            ],
            'edge months' => [
                [
                    'part'  => DateModifierInterface::PART_MONTH,
                    'value' => ['start' => 1, 'end' => 12],
                ],
                [
                    'part'  => DateModifierInterface::PART_MONTH,
                    'value' => ['start' => 1, 'end' => 12],
                    'type'  => null,
                ],
            ],
            'edge months' => [
                [
                    'part'  => DateModifierInterface::PART_DOW,
                    'value' => ['start' => 1, 'end' => 7],
                ],
                [
                    'part'  => DateModifierInterface::PART_DOW,
                    'value' => ['start' => 1, 'end' => 7],
                    'type'  => null,
                ],
            ],
            'edge weeks' => [
                [
                    'part'  => DateModifierInterface::PART_WEEK,
                    'value' => ['start' => 1, 'end' => 53],
                ],
                [
                    'part'  => DateModifierInterface::PART_WEEK,
                    'value' => ['start' => 1, 'end' => 53],
                    'type'  => null,
                ],
            ],
            'edge quaters' => [
                [
                    'part'  => DateModifierInterface::PART_QUARTER,
                    'value' => ['start' => 1, 'end' => 4],
                ],
                [
                    'part'  => DateModifierInterface::PART_QUARTER,
                    'value' => ['start' => 1, 'end' => 4],
                    'type'  => null,
                ],
            ],
            'edge days of year' => [
                [
                    'part'  => DateModifierInterface::PART_DOY,
                    'value' => ['start' => 1, 'end' => 366],
                ],
                [
                    'part'  => DateModifierInterface::PART_DOY,
                    'value' => ['start' => 1, 'end' => 366],
                    'type'  => null,
                ],
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
