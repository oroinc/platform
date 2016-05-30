<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;

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
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

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
        $this->context->expects($this->never())->method('addViolationAt');
        $localization1 = $this->createLocalization('loca1');
        $localization2 = $this->createLocalization('loca2');
        $localization1->setParentLocalization($localization2);

        $this->validator->validate($localization1, $this->constraint);
    }

    public function testValidateWithCircularReference()
    {
        $this->context
            ->expects($this->once())->method('addViolationAt')
            ->with('parentLocalization', $this->constraint->messageCircularReference);

        $localization1 = $this->createLocalization('loca1');
        $localization2 = $this->createLocalization('loca2');
        $localization1->setParentLocalization($localization2);
        $localization2->setParentLocalization($localization2);

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

    public function testUnexpectedItem()
    {
        $this->setExpectedException(
            '\Symfony\Component\Validator\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\LocaleBundle\Model\Localization", "stdClass" given'
        );
        $data = new ArrayCollection([ new \stdClass()]);
        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @param string $name
     * @return Entity\Localization|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createLocalization($name)
    {
        $localization = new Entity\Localization();
        $localization->setName($name);

        return $localization;
    }
}
