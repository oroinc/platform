<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\EntityIdResolverInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdResolverRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityIdResolverRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdResolverInterface */
    private $resolver1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdResolverInterface */
    private $resolver2;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdResolverInterface */
    private $resolver3;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdResolverInterface */
    private $resolver4;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdResolverInterface */
    private $resolver5;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var EntityIdResolverRegistry */
    private $registry;

    protected function setUp()
    {
        $this->resolver1 = $this->createMock(EntityIdResolverInterface::class);
        $this->resolver2 = $this->createMock(EntityIdResolverInterface::class);
        $this->resolver3 = $this->createMock(EntityIdResolverInterface::class);
        $this->resolver4 = $this->createMock(EntityIdResolverInterface::class);
        $this->resolver5 = $this->createMock(EntityIdResolverInterface::class);
        $this->container = TestContainerBuilder::create()
            ->add('resolver1', $this->resolver1)
            ->add('resolver2', $this->resolver2)
            ->add('resolver3', $this->resolver3)
            ->add('resolver4', $this->resolver4)
            ->add('resolver5', $this->resolver5)
            ->getContainer($this);

        $this->registry = new EntityIdResolverRegistry(
            [
                'id1' => [
                    'Class1' => [
                        ['resolver1', 'rest&!json_api'],
                        ['resolver2', 'json_api'],
                        ['resolver3', null]
                    ],
                    'Class2' => [
                        ['resolver4', 'json_api']
                    ]
                ],
                'id2' => [
                    'Class1' => [
                        ['resolver5', null]
                    ]
                ]
            ],
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testGetResolverWhenNoResolverForGivenId()
    {
        self::assertNull(
            $this->registry->getResolver('id3', 'Class1', new RequestType(['json_api']))
        );
    }

    public function testGetResolverWhenNoResolverForGivenClass()
    {
        self::assertNull(
            $this->registry->getResolver('id1', 'Class3', new RequestType(['json_api']))
        );
    }

    public function testGetResolverWhenNoResolverForGivenRequestType()
    {
        self::assertNull(
            $this->registry->getResolver('id1', 'Class2', new RequestType(['rest']))
        );
    }

    public function testGetResolverSpecificForRequestType()
    {
        self::assertSame(
            $this->resolver2,
            $this->registry->getResolver('id1', 'Class1', new RequestType(['json_api']))
        );
    }

    public function testGetResolverDefault()
    {
        self::assertSame(
            $this->resolver3,
            $this->registry->getResolver('id1', 'Class1', new RequestType(['another']))
        );
    }

    public function testGetResolverDefaultWhenOnlyDefaultResolverExistForClass()
    {
        self::assertSame(
            $this->resolver5,
            $this->registry->getResolver('id2', 'Class1', new RequestType(['another']))
        );
    }

    public function testGetResolverWhenNoDefaultResolver()
    {
        self::assertNull(
            $this->registry->getResolver('id1', 'Class2', new RequestType(['another']))
        );
    }

    public function testGetDescriptions()
    {
        $this->resolver1->expects(self::never())
            ->method('getDescription');
        $this->resolver2->expects(self::once())
            ->method('getDescription')
            ->willReturn('resolver2 description');
        $this->resolver3->expects(self::once())
            ->method('getDescription')
            ->willReturn('resolver3 description');
        $this->resolver4->expects(self::once())
            ->method('getDescription')
            ->willReturn('resolver4 description');
        $this->resolver5->expects(self::once())
            ->method('getDescription')
            ->willReturn('resolver5 description');

        self::assertEquals(
            [
                'resolver2 description',
                'resolver3 description',
                'resolver4 description',
                'resolver5 description'
            ],
            $this->registry->getDescriptions(new RequestType(['json_api']))
        );
    }
}
