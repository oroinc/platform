<?php

namespace Oro\Bundle\UIBundle\Tests\Validator\Constraints;

use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Oro\Bundle\UIBundle\Validator\Constraints\MoveToChild;
use Oro\Bundle\UIBundle\Validator\Constraints\MoveToChildValidator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MoveToChildValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MoveToChildValidator */
    protected $validator;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $this->validator = new MoveToChildValidator($this->translator);
        $this->validator->initialize($this->context);
    }

    public function testValidateNotValid()
    {
        $constraint = new MoveToChild();

        $parentTreeItem = new TreeItem('parent', 'Parent');
        $childTreeItem = new TreeItem('child', 'Child');
        $childTreeItem->setParent($parentTreeItem);

        $collection = new TreeCollection();
        $collection->source = [$parentTreeItem];
        $collection->target = $childTreeItem;

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                [$parentTreeItem->getLabel(), [], null, null, 'Parent'],
                [$childTreeItem->getLabel(), [], null, null, 'Child'],
            ]);

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with('Can\'t move node "Parent" to "Child". Node "Child" is a child of "Parent" already.');

        $this->validator->validate($collection, $constraint);
    }

    public function testValidateNotValidRecursive()
    {
        $constraint = new MoveToChild();

        $parentTreeItem = new TreeItem('parent', 'Parent');
        $childFirstLevelTreeItem = new TreeItem('firstLevelChild', 'First Level Child');
        $childFirstLevelTreeItem->setParent($parentTreeItem);
        $childSecondLevelTreeItem = new TreeItem('secondLevelChild', 'Second Level Child');
        $childSecondLevelTreeItem->setParent($childFirstLevelTreeItem);

        $collection = new TreeCollection();
        $collection->source = [$parentTreeItem];
        $collection->target = $childSecondLevelTreeItem;

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                [$parentTreeItem->getLabel(), [], null, null, 'Parent'],
                [$childSecondLevelTreeItem->getLabel(), [], null, null, 'Second Level Child'],
            ]);

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with(sprintf(
                'Can\'t move node "%s" to "%s". Node "%s" is a child of "%s" already.',
                'Parent',
                'Second Level Child',
                'Second Level Child',
                'Parent'
            ));

        $this->validator->validate($collection, $constraint);
    }

    public function testValidateIsValid()
    {
        $constraint = new MoveToChild();

        $parentTreeItem = new TreeItem('parent', 'Parent');
        $childTreeItem = new TreeItem('child', 'Child');
        $childTreeItem->setParent($parentTreeItem);

        $collection = new TreeCollection();
        $collection->source = [$childTreeItem];
        $collection->target = $parentTreeItem;

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->context
            ->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($collection, $constraint);
    }
}
