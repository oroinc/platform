<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Oro\Bundle\UIBundle\Validator\Constraints\MoveToChild;
use Oro\Bundle\UIBundle\Validator\Constraints\MoveToChildValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class MoveToChildValidatorTest extends ConstraintValidatorTestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new MoveToChildValidator($this->translator);
    }

    public function testValidateNotValid()
    {
        $parentTreeItem = new TreeItem('parent', 'Parent');
        $childTreeItem = new TreeItem('child', 'Child');
        $childTreeItem->setParent($parentTreeItem);

        $collection = new TreeCollection();
        $collection->source = [$parentTreeItem];
        $collection->target = $childTreeItem;

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                [$parentTreeItem->getLabel(), [], null, null, 'Parent'],
                [$childTreeItem->getLabel(), [], null, null, 'Child'],
            ]);

        $constraint = new MoveToChild();
        $this->validator->validate($collection, $constraint);

        $this->buildViolation('Can\'t move node "Parent" to "Child". Node "Child" is a child of "Parent" already.')
            ->assertRaised();
    }

    public function testValidateNotValidRecursive()
    {
        $parentTreeItem = new TreeItem('parent', 'Parent');
        $childFirstLevelTreeItem = new TreeItem('firstLevelChild', 'First Level Child');
        $childFirstLevelTreeItem->setParent($parentTreeItem);
        $childSecondLevelTreeItem = new TreeItem('secondLevelChild', 'Second Level Child');
        $childSecondLevelTreeItem->setParent($childFirstLevelTreeItem);

        $collection = new TreeCollection();
        $collection->source = [$parentTreeItem];
        $collection->target = $childSecondLevelTreeItem;

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                [$parentTreeItem->getLabel(), [], null, null, 'Parent'],
                [$childSecondLevelTreeItem->getLabel(), [], null, null, 'Second Level Child'],
            ]);

        $constraint = new MoveToChild();
        $this->validator->validate($collection, $constraint);

        $this
            ->buildViolation(sprintf(
                'Can\'t move node "%s" to "%s". Node "%s" is a child of "%s" already.',
                'Parent',
                'Second Level Child',
                'Second Level Child',
                'Parent'
            ))
            ->assertRaised();
    }

    public function testValidateIsValid()
    {
        $parentTreeItem = new TreeItem('parent', 'Parent');
        $childTreeItem = new TreeItem('child', 'Child');
        $childTreeItem->setParent($parentTreeItem);

        $collection = new TreeCollection();
        $collection->source = [$childTreeItem];
        $collection->target = $parentTreeItem;

        $this->translator->expects($this->never())
            ->method('trans');

        $constraint = new MoveToChild();
        $this->validator->validate($collection, $constraint);

        $this->assertNoViolation();
    }
}
