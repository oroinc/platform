<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SegmentFilterBuilderTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * @var SegmentFilterBuilderType
     */
    private $formType;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        parent::setUp();

        $this->formType = new SegmentFilterBuilderType(
            $this->doctrineHelper,
            $this->tokenStorage
        );
    }

    public function testConfigureOptionsNonManageableEntityClass()
    {
        $entityClass = '\stdClass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn(null);
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Option segment_entity must be a valid entity class, "\stdClass" given');

        $options = [
            'segment_entity' => $entityClass
        ];

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolver->resolve($options);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     */
    public function testConfigureOptionsUnsupportedOptions(array $options)
    {
        $entityClass = '\stdClass';

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn($em);
        $this->expectException(InvalidOptionsException::class);

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolver->resolve($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            'segment_entity unsupported type' => [['segment_entity' => []]],
            'segment_columns unsupported type' => [['segment_entity' => '\stdClass', 'segment_columns' => 'id']],
            'segment_type unsupported type' => [['segment_entity' => '\stdClass', 'segment_type' => []]],
            'segment_name_template unsupported type' => [
                [
                    'segment_entity' => '\stdClass',
                    'segment_name_template' => []
                ]
            ],
            'segment_type unknown value' => [['segment_entity' => '\stdClass', 'segment_type' => 'some_type']],
        ];
    }

    public function testConfigureOptionsDefaultsAndAutoFill()
    {
        $entityClass = '\stdClass';
        $this->assertNormalizersCalls($entityClass);

        $options = [
            'segment_entity' => $entityClass
        ];

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $expected = [
            'segment_entity' => $entityClass,
            'data_class' => Segment::class,
            'segment_type' => SegmentType::TYPE_DYNAMIC,
            'segment_columns' => ['id'],
            'segment_name_template' => 'Auto generated segment %s'
        ];

        $this->assertEquals($expected, $resolver->resolve($options));
    }

    /**
     * @dataProvider formDataProvider
     * @param array $data
     * @param array $expectedDefinition
     */
    public function testSubmitNew(array $data, array $expectedDefinition)
    {
        $entityClass = '\stdClass';
        $options = [
            'segment_entity' => $entityClass
        ];
        $this->assertNormalizersCalls($entityClass);
        $segmentType = new SegmentType(SegmentType::TYPE_DYNAMIC);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(SegmentType::class, SegmentType::TYPE_DYNAMIC)
            ->willReturn($segmentType);

        $owner = new BusinessUnit();
        $organization = new Organization();
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getOwner')
            ->willReturn($owner);
        $user->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $form = $this->factory->create($this->formType, null, $options);

        $form->submit($data);
        /** @var Segment $submittedData */
        $submittedData = $form->getData();
        $this->assertInstanceOf(Segment::class, $submittedData);
        $this->assertEquals($segmentType, $submittedData->getType());
        $this->assertEquals($owner, $submittedData->getOwner());
        $this->assertEquals($organization, $submittedData->getOrganization());
        $this->assertContains('Auto generated segment', $submittedData->getName());
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefinition), $submittedData->getDefinition());
    }

    /**
     * @dataProvider formDataProvider
     * @param array $data
     * @param array $expectedDefinition
     */
    public function testSubmitExisting(array $data, array $expectedDefinition)
    {
        $entityClass = '\stdClass';
        $options = [
            'segment_entity' => $entityClass
        ];
        $this->assertNormalizersCalls($entityClass);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');
        $this->tokenStorage->expects($this->never())
            ->method('getToken');
        $existingEntity = $this->getEntity(Segment::class, ['id' => 2]);

        $form = $this->factory->create($this->formType, $existingEntity, $options);

        $form->submit($data);
        /** @var Segment $submittedData */
        $submittedData = $form->getData();
        $this->assertInstanceOf(Segment::class, $submittedData);
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefinition), $submittedData->getDefinition());
    }

    public function formDataProvider()
    {
        return [
            [
                [
                    'entity' => '\stdClass',
                    'definition' => json_encode([
                        'filters' => [
                            [
                                'columnName' => 'id',
                                'criterion' => [
                                    'filter' => 'number',
                                    'data' => ['value' => 10, 'type' => 3]
                                ]
                            ]
                        ]
                    ])
                ],
                [
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => ['value' => 10, 'type' => 3]
                            ]
                        ]
                    ],
                    'columns' => [
                        [
                            'name' => 'id',
                            'label' => 'id',
                            'sorting' => null,
                            'func' => null
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param $entityClass
     */
    protected function assertNormalizersCalls($entityClass)
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn($em);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($entityClass)
            ->willReturn('id');
    }
}
