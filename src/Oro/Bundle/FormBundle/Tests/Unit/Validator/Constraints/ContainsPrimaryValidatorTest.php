<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Oro\Bundle\FormBundle\Validator\Constraints\ContainsPrimary;
use Oro\Bundle\FormBundle\Validator\Constraints\ContainsPrimaryValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

class ContainsPrimaryValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array or Traversable and ArrayAccess", "boolean" given
     */
    public function testValidateException()
    {
        $constraint = $this->createMock('Symfony\Component\Validator\Constraint');
        $validator = new ContainsPrimaryValidator();
        $validator->validate(false, $constraint);
    }

    public function testShouldKeepLazyCollectionUninitialized()
    {
        /** @var AbstractLazyCollection $collection */
        $collection = $this->getMockForAbstractClass(AbstractLazyCollection::class);
        $validator = new ContainsPrimaryValidator();
        $validator->validate($collection, $this->createMock('Symfony\Component\Validator\Constraint'));

        $this->assertFalse($collection->isInitialized());
    }

    /**
     * @dataProvider validItemsDataProvider
     * @param array $items
     */
    public function testValidateValid(array $items)
    {
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->never())
            ->method('addViolation');

        $constraint = $this->createMock(ContainsPrimary::class);
        $validator = new ContainsPrimaryValidator();
        $validator->initialize($context);

        $validator->validate($items, $constraint);
    }

    /**
     * @return array
     */
    public function validItemsDataProvider()
    {
        return array(
            'no items' => array(
                array()
            ),
            'one item primary' => array(
                array($this->getPrimaryItemMock(true))
            ),
            'more than one item with primary' => array(
                array($this->getPrimaryItemMock(false), $this->getPrimaryItemMock(true))
            ),
            'empty item and primary' => array(
                array(
                    $this->getPrimaryItemMock(false, true),
                    $this->getPrimaryItemMock(true),
                    $this->getPrimaryItemMock(false, true)
                )
            )
        );
    }

    /**
     * @dataProvider invalidItemsDataProvider
     * @param array $items
     */
    public function testValidateInvalid($items)
    {
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->once())
            ->method('addViolation')
            ->with('One of the items must be set as primary.');

        $constraint = $this->createMock(ContainsPrimary::class);
        $validator = new ContainsPrimaryValidator();
        $validator->initialize($context);

        $validator->validate($items, $constraint);
    }

    /**
     * @return array
     */
    public function invalidItemsDataProvider()
    {
        return array(
            'one item' => array(
                array($this->getPrimaryItemMock(false))
            ),
            'more than one item no primary' => array(
                array($this->getPrimaryItemMock(false), $this->getPrimaryItemMock(false))
            ),
            'more than one item more than one primary' => array(
                array($this->getPrimaryItemMock(true), $this->getPrimaryItemMock(true))
            ),
        );
    }

    /**
     * Get primary item mock.
     *
     * @param bool $isPrimary
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPrimaryItemMock($isPrimary)
    {
        $item = $this->getMockBuilder('Oro\Bundle\FormBundle\Entity\PrimaryItem')
            ->disableOriginalConstructor()
            ->getMock();

        $item->expects($this->any())
            ->method('isPrimary')
            ->will($this->returnValue($isPrimary));

        return $item;
    }
}
