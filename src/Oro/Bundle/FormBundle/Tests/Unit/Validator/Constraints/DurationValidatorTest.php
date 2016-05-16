<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\Duration;
use Oro\Bundle\FormBundle\Validator\Constraints\DurationValidator;

class DurationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var DurationValidator */
    protected $validator;

    protected function setUp()
    {
        $this->validator = new DurationValidator();
    }

    /**
     * @dataProvider validStringsDataProvider
     *
     * @param string $value
     */
    public function testValidateValid($value)
    {
        $context = $this
            ->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $context
            ->expects($this->never())
            ->method('addViolation')
        ;

        $constraint = new Duration();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validStringsDataProvider()
    {
        return [
            [null],
            [''],
            ['0'],
            ['0:0'],
            ['0:0:0'],
            ['01:02:03'],
            ['77:77:77'],
            ['1:2:3'],
            ['1:2'],
            ['1'],
            ['1h'],
            ['1h 2m'],
            ['1h 2m 3s'],
            ['2m 3s'],
            ['3s'],
            ['1h 3s'],
            ['1.5h 5m'],
            ['77h 77m'],
            ['1h2m3s'],
            ['0.5s'],
        ];
    }

    /**
     * @dataProvider invalidStringsDataProvider
     *
     * @param string $value
     */
    public function testValidateInvalidValue($value)
    {
        $constraint = new Duration();

        $context = $this
            ->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $context
            ->expects($this->once())
            ->method('addViolation')
        ;

        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function invalidStringsDataProvider()
    {
        return [
            ['test'],
            ['1a'],
            ['0.5'],
            ['2:3:4:5'],
            ['::1'],
            ['1h 2m test'],
            [' 1h 2m'],
            ['0 1h 2m'],
            [PHP_INT_MAX . ':' . PHP_INT_MAX . ':' . PHP_INT_MAX],
        ];
    }
}
