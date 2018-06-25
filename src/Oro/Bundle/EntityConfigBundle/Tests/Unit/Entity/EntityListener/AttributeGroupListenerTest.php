<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\EntityListener\AttributeGroupListener;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints\AttributeGroupStub;

class AttributeGroupListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeGroupListener */
    private $listener;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->listener = new AttributeGroupListener(new SlugGenerator());
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testPrePersistCodeExist()
    {
        $group = new AttributeGroupStub(1, 'label');
        $group->setCode('some-code');

        $this->em->expects($this->never())->method('getRepository');

        $eventArgs = new LifecycleEventArgs($group, $this->em);
        $this->listener->prePersist($group, $eventArgs);
    }

    /**
     * @dataProvider prePersistDataProvider
     *
     * @param AttributeGroup $group
     * @param array          $repositoryArgs
     * @param array          $repositoryResults
     * @param string         $expectedCodeSlug
     */
    public function testPrePersist(
        AttributeGroup $group,
        array $repositoryArgs,
        array $repositoryResults,
        $expectedCodeSlug
    ) {
        $eventArgs = new LifecycleEventArgs($group, $this->em);

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly(count($repositoryArgs)))
            ->method('findOneBy')
            ->withConsecutive(...$repositoryArgs)
            ->willReturnOnConsecutiveCalls(...$repositoryResults);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AttributeGroup::class)
            ->willReturn($repository);

        $this->listener->prePersist($group, $eventArgs);
        $this->assertEquals($expectedCodeSlug, $group->getCode());
    }

    /**
     * @return array
     */
    public function prePersistDataProvider()
    {
        $group1 = new AttributeGroupStub(1, 'проверка транслитерации!');
        $group1->setAttributeFamily((new AttributeFamily())->setCode('group1Family'));
        $group2 = new AttributeGroupStub(1, '!!!&&&&!!!');
        $group2->setAttributeFamily((new AttributeFamily())->setCode('group2Family'));

        return [
            'code slug generated from label' => [
                'group' => $group1,
                'repositoryArgs' => [
                    [[ 'attributeFamily' => $group1->getAttributeFamily(), 'code' => 'proverka_transliteracii' ]],
                    [[ 'attributeFamily' => $group1->getAttributeFamily(), 'code' => 'proverka_transliteracii1' ]],
                ],
                'repositoryResults' => [$group1, null],
                'expectedSlug' => 'proverka_transliteracii1',
            ],
            'default unique slug created' => [
                'group' => $group2,
                'repositoryArgs' => [
                    [
                        [
                            'attributeFamily' => $group2->getAttributeFamily(),
                            'code' => AttributeGroupListener::DEFAULT_SLUG
                        ]
                    ],
                    [
                        [
                            'attributeFamily' => $group2->getAttributeFamily(),
                            'code' => AttributeGroupListener::DEFAULT_SLUG . 1
                        ]
                    ],
                    [
                        [
                            'attributeFamily' => $group2->getAttributeFamily(),
                            'code' => AttributeGroupListener::DEFAULT_SLUG . 2
                        ]
                    ]
                ],
                'repositoryResults' => [$group2, $group2, null],
                'expectedSlug' => AttributeGroupListener::DEFAULT_SLUG . 2,
            ]
        ];
    }
}
