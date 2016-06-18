<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Acl\Voter\LocalizationVoter;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = 'Oro\Bundle\LocaleBundle\Entity\Localization';

    /** @var LocalizationRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var LocalizationVoter */
    protected $voter;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($object) {
                    return get_class($object);
                }
            );
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                function ($object) {
                    return method_exists($object, 'getId') ? $object->getId() : null;
                }
            );

        $this->voter = new LocalizationVoter($this->doctrineHelper);
        $this->voter->setClassName(self::ENTITY_CLASS);
    }

    /**
     * @dataProvider voteDataProvider
     *
     * @param int $count
     * @param object $object
     * @param string $attribute
     * @param int $expected
     */
    public function testVote($count, $object, $attribute, $expected)
    {
        $this->doctrineHelper->expects($this->exactly((int)($count !== null)))
            ->method('getEntityRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->repository);

        $this->repository->expects($this->exactly((int)($count !== null)))
            ->method('getLocalizationsCount')
            ->willReturn($count);

        $this->assertEquals($expected, $this->voter->vote($this->getToken(), $object, [$attribute]));
    }

    /**
     * @return array
     */
    public function voteDataProvider()
    {
        $localization = $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', ['id' => 42]);

        return [
            'abstain when not supported attribute' => [
                'count' => null,
                'object' => $localization,
                'attribute' => 'TEST',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when not supported class' => [
                'count' => null,
                'object' => $this->getEntity('Oro\Bundle\TestFrameworkBundle\Entity\Item', ['id' => 42]),
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when new entity' => [
                'count' => null,
                'object' => $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization'),
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when more than one entity' => [
                'count' => 2,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'denied when count is 0' => [
                'count' => 0,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'denied when count is 1' => [
                'count' => 1,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_DENIED,
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    protected function getToken()
    {
        return $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
