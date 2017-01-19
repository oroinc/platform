<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Form\Type\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\SkipEmptyPeriodsFilterType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SkipEmptyPeriodsFilterTypeTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
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
            SkipEmptyPeriodsFilterType::NAME,
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
            'type_no' => [
                [
                    'value' => false,
                ],
                [
                    'value' => false,
                    'type' => null,
                ]
            ],
            'type_yes' => [
                [
                    'value' => true,
                ],
                [
                    'value' => true,
                    'type' => null,
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
