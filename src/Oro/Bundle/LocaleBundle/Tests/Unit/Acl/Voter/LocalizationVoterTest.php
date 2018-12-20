<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Acl\Voter\LocalizationVoter;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LocalizationVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    const ENTITY_CLASS = 'Oro\Bundle\LocaleBundle\Entity\Localization';

    /** @var LocalizationRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

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

        $this->configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->voter = new LocalizationVoter($this->doctrineHelper, $this->configManager);
        $this->voter->setClassName(self::ENTITY_CLASS);
    }

    /**
     * @dataProvider voteDataProvider
     *
     * @param int $count
     * @param int $defaultLocalization
     * @param object $object
     * @param string $attribute
     * @param int $expected
     */
    public function testVote($count, $defaultLocalization, $object, $attribute, $expected)
    {
        $this->doctrineHelper
            ->method('getEntityRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->repository);

        $this->repository
            ->method('getLocalizationsCount')
            ->willReturn($count);
        $this->configManager->method('get')->willReturn($defaultLocalization);

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
                'default_localization' => 1,
                'object' => $localization,
                'attribute' => 'TEST',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when not supported class' => [
                'count' => null,
                'default_localization' => 1,
                'object' => $this->getEntity('Oro\Bundle\TestFrameworkBundle\Entity\Item', ['id' => 42]),
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when new entity' => [
                'count' => null,
                'default_localization' => 1,
                'object' => $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization'),
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when more than one entity' => [
                'count' => 2,
                'default_localization' => 1,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'denied when count is 0' => [
                'count' => 0,
                'default_localization' => 1,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'denied when count is 1' => [
                'count' => 1,
                'default_localization' => 1,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'denied when localization used in config' => [
                'count' => 2,
                'default_localization' => 42,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenInterface
     */
    protected function getToken()
    {
        return $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
