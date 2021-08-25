<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Acl\Voter\LocalizationVoter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LocalizationVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LocalizationRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($object) {
                return method_exists($object, 'getId') ? $object->getId() : null;
            });

        $this->configManager = $this->createMock(ConfigManager::class);

        $container = TestContainerBuilder::create()
            ->add('oro_config.manager', $this->configManager)
            ->getContainer($this);

        $this->voter = new LocalizationVoter($this->doctrineHelper, $container);
        $this->voter->setClassName(Localization::class);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(?int $count, int $defaultLocalization, object $object, string $attribute, int $expected)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(Localization::class)
            ->willReturn($this->repository);

        $this->repository->expects($this->any())
            ->method('getLocalizationsCount')
            ->willReturn($count);
        $this->configManager->method('get')->willReturn($defaultLocalization);

        $this->assertEquals(
            $expected,
            $this->voter->vote($this->createMock(TokenInterface::class), $object, [$attribute])
        );
    }

    public function voteDataProvider(): array
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);

        $item = new Item();
        ReflectionUtil::setId($item, 42);

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
                'object' => $item,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when new entity' => [
                'count' => null,
                'default_localization' => 1,
                'object' => new Localization(),
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
}
