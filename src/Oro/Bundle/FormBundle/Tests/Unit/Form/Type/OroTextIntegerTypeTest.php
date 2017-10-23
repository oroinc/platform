<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroTextIntegerType;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\Regex;

class OroTextIntegerTypeTest extends TypeTestCase
{
    /**
     * @var OroDateTimeType
     */
    private $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new OroTextIntegerType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_text_integer', $this->type->getName());
    }

    public function testSetDefaultOptions()
    {
        $expectedOptions = [
            // deprecated as of Symfony 2.7, to be removed in Symfony 3.0.
            'precision' => null,
            // default scale is locale specific (usually around 3)
            'scale' => null,
            'grouping' => false,
            // Integer cast rounds towards 0, so do the same when displaying fractions
            'rounding_mode' => IntegerToLocalizedStringTransformer::ROUND_DOWN,
            'compound' => false,
            'constraints' => [
                new Regex(
                    [
                        'pattern' => '/^[\d+]*$/',
                        'message' => 'This value should contain only numbers.',
                    ]
                ),
            ],
        ];

        $form = $this->factory->create($this->type);
        $form->submit((new \DateTime()));

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }

    /**
     * @dataProvider valuesDataProvider
     *
     * @param string    $value
     * @param \DateTime $expectedValue
     */
    public function testSubmitValidData($value, $expectedValue)
    {
        $form = $this->factory->create($this->type);
        $form->submit($value);
        static::assertTrue($form->isValid());
        static::assertSame($expectedValue, $form->getData());
    }

    /**
     * @return array
     */
    public function valuesDataProvider()
    {
        return [
            [
                '123',
                123,
            ],
            [
                123,
                123,
            ],
            [
                555,
                555,
            ],
        ];
    }
}
