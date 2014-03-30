<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreateOrSelectTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;

class EntityCreateOrSelectTransformerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'TestClass';
    const DEFAULT_MODE = 'default';

    /**
     * @var EntityCreateOrSelectTransformer
     */
    protected $transformer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will(
                $this->returnCallback(
                    function (TestEntity $entity) {
                        return $entity->getId();
                    }
                )
            );

        $this->transformer = new EntityCreateOrSelectTransformer(
            $this->doctrineHelper,
            self::CLASS_NAME,
            self::DEFAULT_MODE
        );
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper);
        unset($this->transformer);
    }

    /**
     * @param mixed $value
     * @param array $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, array $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider()
    {
        return array(
            'no value' => array(
                'value' => null,
                'expected' => array(
                    'new_entity' => null,
                    'existing_entity' => null,
                    'mode' => self::DEFAULT_MODE
                )
            ),
            'new entity' => array(
                'value' => new TestEntity(),
                'expected' => array(
                    'new_entity' => new TestEntity(),
                    'existing_entity' => null,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                )
            ),
            'existing entity' => array(
                'value' => new TestEntity(1),
                'expected' => array(
                    'new_entity' => null,
                    'existing_entity' => new TestEntity(1),
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                )
            ),
        );
    }

    /**
     * @param mixed $value
     * @param string $exception
     * @param string $message
     * @dataProvider transformExceptionDataProvider
     */
    public function testTransformException($value, $exception, $message)
    {
        $this->setExpectedException($exception, $message);

        $this->transformer->transform($value);
    }

    public function transformExceptionDataProvider()
    {
        return array(
            'invalid type' => array(
                'value' => 'not an object',
                'exception' => '\Symfony\Component\Form\Exception\UnexpectedTypeException',
                'message' => 'Expected argument of type "object", "string" given',
            )
        );
    }

    /**
     * @param mixed $value
     * @param mixed $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider()
    {
        return array(
            'null' => array(
                'value' => null,
                'expected' => null,
            ),
            'create mode' => array(
                'value' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'new_entity' => new TestEntity(),
                ),
                'expected' => new TestEntity(),
            ),
            'view mode' => array(
                'value' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                    'existing_entity' => new TestEntity(1),
                ),
                'expected' => new TestEntity(1),
            ),
            'grid mode' => array(
                'value' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                ),
                'expected' => null,
            ),
        );
    }

    /**
     * @param mixed $value
     * @param string $exception
     * @param string $message
     * @dataProvider reverseTransformExceptionDataProvider
     */
    public function testReverseTransformException($value, $exception, $message)
    {
        $this->setExpectedException($exception, $message);

        $this->transformer->reverseTransform($value);
    }

    public function reverseTransformExceptionDataProvider()
    {
        return array(
            'invalid type' => array(
                'value' => new TestEntity('not an array'),
                'exception' => '\Symfony\Component\Form\Exception\UnexpectedTypeException',
                'message' => 'Expected argument of type "array",'
                    . ' "Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity" given',
            ),
            'empty mode' => array(
                'value' => array(),
                'exception' => '\Symfony\Component\Form\Exception\TransformationFailedException',
                'message' => 'Data parameter "mode" is required',
            ),
            'no new entity' => array(
                'value' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                ),
                'exception' => '\Symfony\Component\Form\Exception\TransformationFailedException',
                'message' => 'Data parameter "new_entity" is required',
            ),
            'no existing entity' => array(
                'value' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                ),
                'exception' => '\Symfony\Component\Form\Exception\TransformationFailedException',
                'message' => 'Data parameter "existing_entity" is required',
            ),
        );
    }
}
