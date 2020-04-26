<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Exception\NotFoundSupportedOwnershipDecisionMakerException;
use Oro\Bundle\SecurityBundle\Owner\ChainEntityOwnershipDecisionMaker;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;

class ChainEntityOwnershipDecisionMakerTest extends \PHPUnit\Framework\TestCase
{
    public function testPassOwnershipDecisionMakersThroughConstructor()
    {
        $maker = $this->createMock(AccessLevelOwnershipDecisionMakerInterface::class);
        $maker->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $chain = new ChainEntityOwnershipDecisionMaker([$maker]);

        $this->assertTrue($chain->supports());
    }

    public function testAddOwnershipDecisionMaker()
    {
        $negative = $this->createMock(AccessLevelOwnershipDecisionMakerInterface::class);
        $negative->expects($this->once()) // only 1 time, despite adding it three times
            ->method('supports')
            ->willReturn(false);

        $positive = $this->createMock(AccessLevelOwnershipDecisionMakerInterface::class);
        $positive->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $chain = new ChainEntityOwnershipDecisionMaker([$negative]);
        $chain->addOwnershipDecisionMaker($negative);
        $chain->addOwnershipDecisionMaker($negative);
        $chain->addOwnershipDecisionMaker($positive);

        $this->assertTrue($chain->supports());
    }

    public function testSupports()
    {
        $chain = new ChainEntityOwnershipDecisionMaker();
        $this->assertFalse($chain->supports());
        $chain->addOwnershipDecisionMaker($this->getOwnershipDecisionMakerMock(false));
        $this->assertFalse($chain->supports());

        $chain->addOwnershipDecisionMaker($this->getOwnershipDecisionMakerMock(true));
        $this->assertTrue($chain->supports());
    }

    /**
     * @dataProvider isOwnerEntityDataProvider
     *
     * @param string $levelMethod
     * @param bool $result
     */
    public function testIsOwnerEntity($levelMethod, $result)
    {
        /** @var MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->createMock(AccessLevelOwnershipDecisionMakerInterface::class);
        $maker->expects($this->atLeastOnce())
            ->method('supports')
            ->willReturn(true);
        $maker->expects($this->atLeastOnce())
            ->method($levelMethod)
            ->will($this->returnValue($result));

        $chain = new ChainEntityOwnershipDecisionMaker();
        $chain->addOwnershipDecisionMaker($maker);
        $this->assertEquals($result, $chain->$levelMethod(new \stdClass()));
        $this->assertEquals($result, $chain->$levelMethod(new \stdClass()));
    }

    /**
     * @return array
     */
    public function isOwnerEntityDataProvider()
    {
        return [
            'positive isOrganization' => [
               'levelMethod' => 'isOrganization',
               'result' => true
            ],
            'negative isOrganization' => [
                'levelMethod' => 'isOrganization',
                'result' => false
            ],
            'positive isBusinessUnit' => [
                'levelMethod' => 'isBusinessUnit',
                'result' => true
            ],
            'negative isBusinessUnit' => [
                'levelMethod' => 'isBusinessUnit',
                'result' => false
            ],
            'positive isUser' => [
                'levelMethod' => 'isUser',
                'result' => true
            ],
            'negative isUser' => [
                'levelMethod' => 'isUser',
                'result' => false
            ]
        ];
    }

    public function testNotFoundSupportedOwnershipDecisionMakerException()
    {
        $this->expectException(NotFoundSupportedOwnershipDecisionMakerException::class);
        $this->expectExceptionMessage('Not found supported ownership decision maker in chain');

        $maker = $this->getOwnershipDecisionMakerMock(false);
        $chain = new ChainEntityOwnershipDecisionMaker();
        $chain->addOwnershipDecisionMaker($maker);
        $chain->isOrganization(new \stdClass());
    }

    /**
     * @dataProvider isAssociatedWithLevelEntityDataProvider
     *
     * @param string $associatedLevelMethod
     * @param bool $result
     */
    public function testIsAssociatedWithLevelEntity($associatedLevelMethod, $result)
    {
        $user = $this->createMock(UserInterface::class);
        $domainObject = new \stdClass();

        $maker = $this->getOwnershipDecisionMakerMock(true);
        $maker->expects($this->once())
            ->method($associatedLevelMethod)
            ->will($this->returnValue($result));

        $chain = new ChainEntityOwnershipDecisionMaker();
        $chain->addOwnershipDecisionMaker($maker);
        $this->assertEquals(
            $result,
            $chain->$associatedLevelMethod(
                $user,
                $domainObject
            )
        );
    }

    public function isAssociatedWithLevelEntityDataProvider()
    {
        return [
            'positive isAssociatedWithBusinessUnit' => [
                'levelMethod' => 'isAssociatedWithBusinessUnit',
                'result' => true
            ],
            'negative isAssociatedWithBusinessUnit' => [
                'levelMethod' => 'isAssociatedWithBusinessUnit',
                'result' => false
            ],
            'positive isAssociatedWithUser' => [
                'levelMethod' => 'isAssociatedWithUser',
                'result' => true
            ],
            'negative isAssociatedWithUser' => [
                'levelMethod' => 'isAssociatedWithUser',
                'result' => false
            ],
            'positive isAssociatedWithOrganization' => [
                'levelMethod' => 'isAssociatedWithOrganization',
                'result' => true
            ],
            'negative isAssociatedWithOrganization' => [
                'levelMethod' => 'isAssociatedWithOrganization',
                'result' => false
            ]
        ];
    }

    /**
     * @param bool $supports
     * @return AccessLevelOwnershipDecisionMakerInterface|MockObject
     */
    protected function getOwnershipDecisionMakerMock($supports = true)
    {
        /** @var MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->createMock(AccessLevelOwnershipDecisionMakerInterface::class);
        $maker->expects($this->atLeastOnce())
            ->method('supports')
            ->willReturn($supports);

        return $maker;
    }
}
