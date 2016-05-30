<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;

use Oro\Bundle\LocaleBundle\Entity;
use Oro\Bundle\LocaleBundle\Validator\Constraints;
use Oro\Bundle\LocaleBundle\Validator\Constraints\LocalizationValidator;

class LocalizationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Constraints\Localization */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface */
    protected $context;

    /** @var LocalizationValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new Constraints\Localization();
        $this->context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new LocalizationValidator();
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'oro_locale.localization_validator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithoutCircularReference()
    {
        $this->context->expects($this->never())->method('buildViolation');
        $localization1 = $this->createLocalization('loca1', 1);
        $localization2 = $this->createLocalization('loca2', 2);
        $localization1->setParentLocalization($localization2);

        $this->validator->validate($localization1, $this->constraint);
    }

    public function testValidateWithCircularReference()
    {
        $this->expectViolation();

        $localization1 = $this->createLocalization('loca1', 1);
        $localization2 = $this->createLocalization('loca2', 2);
        $localization1->setParentLocalization($localization2);
        $localization1->addChildLocalization($localization2);

        $this->validator->validate($localization1, $this->constraint);
    }

    public function testValidateSelfPatrent()
    {
        $this->expectViolation();

        $localization1 = $this->createLocalization('loca1', 1);
        $localization1->setParentLocalization($localization1);

        $this->validator->validate($localization1, $this->constraint);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Bundle\LocaleBundle\Entity\Localization", "string" given
     */
    public function testUnexpectedValue()
    {
        $this->validator->validate('test', $this->constraint);
    }

    public function testUnexpectedClass()
    {
        $this->setExpectedException(
            '\Symfony\Component\Validator\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\LocaleBundle\Entity\Localization", "stdClass" given'
        );
        $data = new \stdClass();
        $this->validator->validate($data, $this->constraint);
    }

    private function expectViolation()
    {
        $violationBuilder = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('parentLocalization')
            ->willReturnSelf();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->messageCircularReference)
            ->willReturn($violationBuilder);
    }

    /**
     * @param string $name
     * @param int $id
     * @return Entity\Localization
     */
    private function createLocalization($name, $id)
    {
        $localization = new Entity\Localization();
        $localization->setName($name);
        $reflection = new \ReflectionClass('Oro\Bundle\LocaleBundle\Entity\Localization');
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($localization, $id);

        return $localization;
    }
}
