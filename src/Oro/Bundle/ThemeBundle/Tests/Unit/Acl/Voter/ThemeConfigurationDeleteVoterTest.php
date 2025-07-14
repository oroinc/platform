<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\ThemeBundle\Acl\Voter\ThemeConfigurationDeleteVoter;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ThemeConfigurationDeleteVoterTest extends TestCase
{
    private EntityRepository&MockObject $repository;
    private TokenInterface&MockObject $token;
    private DoctrineHelper&MockObject $doctrineHelper;
    private ThemeConfigurationDeleteVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new ThemeConfigurationDeleteVoter($this->doctrineHelper);
        $this->voter->setClassName(ThemeConfiguration::class);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(mixed $configValue, int $expected): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ConfigValue::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('findBy')
            ->with([
                'section' => Configuration::ROOT_NAME,
                'name' => Configuration::THEME_CONFIGURATION,
                'textValue' => 1
            ])
            ->willReturn($configValue);

        $result = $this->voter->vote(
            $this->token,
            new DomainObjectReference(ThemeConfiguration::class, 1, 2),
            [BasicPermission::DELETE]
        );

        self::assertEquals($expected, $result);
    }

    public function voteDataProvider(): array
    {
        return [
            'is selected in the theme configuration' => [
                'configValue' => new ConfigValue(),
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'no selected in the theme configuration' => [
                'configValue' => null,
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
        ];
    }

    /**
     * @dataProvider voteNoSupportsDataProvider
     */
    public function testVoteNoSupports(string|DomainObjectReference $subject, array $attributes): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityRepository')
            ->with(ConfigValue::class)
            ->willReturn($this->repository);

        $result = $this->voter->vote($this->token, $subject, $attributes);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function voteNoSupportsDataProvider(): array
    {
        return [
            'wrong attribute' => [
                'subject' => new DomainObjectReference(ThemeConfiguration::class, 1, 2),
                'attributes' => [BasicPermission::EDIT],
            ],
            'no object' => [
                'subject' => '',
                'attributes' => [BasicPermission::DELETE],
            ],
            'wrong object' => [
                'subject' => new DomainObjectReference(\stdClass::class, 1, 2),
                'attributes' => [BasicPermission::DELETE],
            ],
        ];
    }
}
