<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Exception\InvalidMaxNestingLevelException;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevel;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevelValidator;

use Symfony\Component\Validator\ExecutionContextInterface;

class MaxNestedLevelValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var MaxNestedLevelValidator */
    protected $validator;

    /** @var MenuUpdateManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $menuUpdateManager;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    protected function setUp()
    {
        $this->context = $this->getMock(ExecutionContextInterface::class);

        $this->menuUpdateManager = $this->getMock(MenuUpdateManager::class, [], [], '', false);

        $this->validator = new MaxNestedLevelValidator($this->menuUpdateManager);
        $this->validator->initialize($this->context);
    }


    public function testValidate()
    {
        $constraint = new MaxNestedLevel();

        $update = new MenuUpdateStub();

        $message = sprintf(
            "Item \"%s\" can't be saved. Max nesting level for menu \"%s\" is %d.",
            'default-title',
            'test-menu',
            1
        );

        $this->menuUpdateManager
            ->expects($this->once())
            ->method('checkMaxNestingLevel')
            ->with($update)
            ->will($this->throwException(new InvalidMaxNestingLevelException($message)));

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with($message);

        $this->validator->validate($update, $constraint);
    }
}
