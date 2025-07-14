<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class PropertyAccessorArrayAccessTest extends TestCase
{
    protected PropertyAccessorInterface $propertyAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            null,
            PropertyAccessorWithDotArraySyntax::THROW_ON_INVALID_INDEX
        );
    }

    abstract protected function getContainer(array $array);

    public function getValidPropertyPaths()
    {
        return [
            [$this->getContainer(['firstName' => 'John']), 'firstName', 'John'],
            [$this->getContainer(['firstName' => 'John']), '[firstName]', 'John'],
            [
                $this->getContainer(['person' => $this->getContainer(['firstName' => 'John'])]),
                'person.firstName',
                'John'
            ],
            [
                $this->getContainer(['person' => $this->getContainer(['firstName' => 'John'])]),
                'person[firstName]',
                'John'
            ],
            [
                $this->getContainer(['person' => $this->getContainer(['firstName' => 'John'])]),
                '[person][firstName]',
                'John'
            ],
            [
                $this->getContainer(['person' => $this->getContainer(['firstName' => 'John'])]),
                '[person].firstName',
                'John'
            ],
        ];
    }

    public function getPathsWithMissingIndex()
    {
        return [
            [$this->getContainer(['firstName' => 'John']), 'lastName'],
            [$this->getContainer(['firstName' => 'John']), '[lastName]'],
            [$this->getContainer([]), 'index.lastName'],
            [$this->getContainer([]), 'index[lastName]'],
            [$this->getContainer([]), '[index][lastName]'],
            [$this->getContainer([]), '[index].lastName'],
            [$this->getContainer(['index' => []]), 'index.lastName'],
            [$this->getContainer(['index' => []]), '[index][lastName]'],
            [$this->getContainer(['index' => ['firstName' => 'John']]), 'index.lastName'],
            [$this->getContainer(['index' => ['firstName' => 'John']]), '[index][lastName]'],
        ];
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testGetValue($collection, $path, $value): void
    {
        $this->assertSame($value, $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testGetValueThrowsExceptionIfIndexNotFound($collection, $path): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->propertyAccessor->getValue($collection, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testGetValueThrowsNoExceptionIfIndexNotFoundAndIndexExceptionsDisabled($collection, $path): void
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            null,
            PropertyAccessorWithDotArraySyntax::DO_NOT_THROW
        );
        $this->assertNull($this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($collection, $path): void
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFound($collection, $path): void
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testRemove($collection, $path): void
    {
        $this->propertyAccessor->remove($collection, $path);

        try {
            $this->propertyAccessor->getValue($collection, $path);
            $this->fail(sprintf('It is expected that "%s" is removed.', $path));
        } catch (NoSuchPropertyException $ex) {
        }
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testRemoveThrowsNoExceptionIfIndexNotFound($collection, $path): void
    {
        $clone = unserialize(serialize($collection));

        $this->propertyAccessor->remove($collection, $path);

        $this->assertEquals($clone, $collection);
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsReadable($collection, $path): void
    {
        $this->assertTrue($this->propertyAccessor->isReadable($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsWritable($collection, $path): void
    {
        $this->assertTrue($this->propertyAccessor->isWritable($collection, $path));
    }
}
