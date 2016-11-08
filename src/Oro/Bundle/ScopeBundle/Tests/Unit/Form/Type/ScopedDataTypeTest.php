<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\Type\ScopedDataType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;

class ScopedDataTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var ScopedDataType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    StubType::NAME => new StubType(),
                ],
                []
            ),
        ];
    }

    protected function setUp()
    {
        parent::setUp();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getReference')
            ->with(Scope::class)
            ->will(
                $this->returnCallback(
                    function ($class, $id) {
                        return $this->getEntity($class, ['id' => $id]);
                    }
                )
            );

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $this->formType = new ScopedDataType($registry);
    }

    public function testSubmit()
    {
        $scope4 = $this->getEntity(Scope::class, ['id' => 4]);
        $scope6 = $this->getEntity(Scope::class, ['id' => 6]);
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        $form = $this->factory->create(
            $this->formType,
            [],
            [
                'type' => StubType::NAME,
                'scopes' => [$scope4, $scope6, $scope1],
                'preloaded_scopes' => [$scope4, $scope6],
                'options' => [
                    StubType::REQUIRED_OPTION => 'test_value',
                ],
            ]
        );

        // assert that form was built with all preloaded scopes
        $this->assertSame(2, $form->count());
        $this->assertTrue($form->has(4));
        $this->assertTrue($form->has(6));

        $submittedData = [
            4 => [
                StubType::FIELD_1 => 'scope4_field1',
                StubType::FIELD_2 => 'scope4_field2',
            ],
            6 => [
                StubType::FIELD_1 => 'scope6_field1',
                StubType::FIELD_2 => 'scope6_field2',
            ],
            1 => [
                StubType::FIELD_1 => 'scope1_field1',
                StubType::FIELD_2 => 'scope1_field2',
            ],
        ];
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        // assert that when submitted all scopes added to form
        $this->assertSame(3, $form->count());
        $this->assertSame($submittedData[4], $form->get('4')->getData());
        $this->assertSame($submittedData[6], $form->get('6')->getData());
        $this->assertSame($submittedData[1], $form->get('1')->getData());
    }

    public function testBuildView()
    {
        $view = new FormView();
        $scopes = [
            $this->getEntity(Scope::class, ['id' => 4]),
            $this->getEntity(Scope::class, ['id' => 6]),
            $this->getEntity(Scope::class, ['id' => 1]),
        ];
        $form = $this->factory->create(
            $this->formType,
            [],
            [
                'type' => StubType::NAME,
                'scopes' => $scopes,
                'options' => [
                    StubType::REQUIRED_OPTION => 'test_value',
                ],
            ]
        );
        $this->formType->buildView($view, $form, []);

        $this->assertSame($scopes, $view->vars['scopes']);
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
    {
        return [
            [
                'children' => ['1' => 'test'],
                'expected' => [],
            ],
            [
                'children' => ['1' => 'test', 'not_int' => 'test'],
                'expected' => ['not_int' => 'test'],
            ],
            [
                'children' => ['1' => 'test', 'not_int' => 'test'],
                'expected' => ['1' => 'test', 'not_int' => 'test'],
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ScopedDataType::NAME, $this->formType->getName());
    }
}
