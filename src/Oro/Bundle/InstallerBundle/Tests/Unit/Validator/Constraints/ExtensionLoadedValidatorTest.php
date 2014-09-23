<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Symfony\Component\Validator\ExecutionContextInterface;

use Oro\Bundle\InstallerBundle\Validator\Constraints\ExtensionLoaded;
use Oro\Bundle\InstallerBundle\Validator\Constraints\ExtensionLoadedValidator;

class ExtensionLoadedValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ExtensionLoadedValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = new ExtensionLoadedValidator();
    }

    /**
     * @param mixed $data
     * @param bool  $expected
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate($data, $expected)
    {
        $constraint = new ExtensionLoaded();

        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        if ($expected) {
            $this->validator->initialize($this->context);

            $this->context
                ->expects($this->once())
                ->method('addViolation')
                ->with($this->isType('string'));
        }

        $this->validator->validate($data, $constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        $extensions = get_loaded_extensions();

        return [
            [null, true],
            [false, true],
            [true, true],
            [1, true],
            [0, true],
            ['extension_not_loaded', true],
            [reset($extensions), false],
        ];
    }

    /**
     * @param mixed $data
     *
     * @dataProvider invalidDataProvider
     */
    public function testInvalidData($data)
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            sprintf('Value is type of %s, string is required', gettype($data))
        );
        $constraint = new ExtensionLoaded();
        $this->validator->validate($data, $constraint);
    }

    /**
     * @return array
     */
    public function invalidDataProvider()
    {
        return [
            [[]],
            [new \stdClass()],
        ];
    }
}
