<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Voter\AssociationAwareVoter;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsOrganization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AssociationAwareVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var AssociationAwareVoter */
    private $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->voter = new AssociationAwareVoter(
            $this->authorizationChecker,
            PropertyAccess::createPropertyAccessor(),
            CmsUser::class,
            'organization'
        );
    }

    private function getEntity(?CmsOrganization $associatedEntity): CmsUser
    {
        $entity = new CmsUser();
        $entity->setOrganization($associatedEntity);

        return $entity;
    }

    public function testVoteForNotObject(): void
    {
        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                CmsUser::class,
                ['VIEW']
            )
        );
    }

    public function testVoteForNotSupportedEntity(): void
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                new CmsArticle(),
                ['VIEW']
            )
        );
    }

    /**
     * @dataProvider voteNullAssociationDataProvider
     */
    public function testVoteNullAssociation(array $attributes): void
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                $this->getEntity(null),
                $attributes
            )
        );
    }

    public static function voteNullAssociationDataProvider(): array
    {
        return [
            [['VIEW']],
            [['CREATE']],
            [['EDIT']],
            [['DELETE']],
            [['OTHER']]
        ];
    }

    public function testVoteWhenNotSupportedAttributes(): void
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                $this->getEntity(new CmsOrganization()),
                ['ATTR_1', 'ATTR_2']
            )
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testVoteWhenAccessToAssociatedEntityDenied(
        array $attributes,
        string $associatedEntityAttribute
    ): void {
        $associatedEntity = new CmsOrganization();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($associatedEntityAttribute, self::identicalTo($associatedEntity))
            ->willReturn(false);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                $this->getEntity($associatedEntity),
                $attributes
            )
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testVoteWhenAccessToAssociatedEntityGranted(
        array $attributes,
        string $associatedEntityAttribute
    ): void {
        $associatedEntity = new CmsOrganization();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($associatedEntityAttribute, self::identicalTo($associatedEntity))
            ->willReturn(true);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                $this->getEntity($associatedEntity),
                $attributes
            )
        );
    }

    public static function supportedAttributesDataProvider(): array
    {
        return [
            [['VIEW'], 'VIEW'],
            [['CREATE'], 'EDIT'],
            [['EDIT'], 'EDIT'],
            [['DELETE'], 'EDIT']
        ];
    }

    public function testVoteWhenAccessToAssociatedEntityGrantedByAllAttributes(): void
    {
        $associatedEntity = new CmsOrganization();

        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($associatedEntity)],
                ['EDIT', self::identicalTo($associatedEntity)]
            )
            ->willReturn(true);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                $this->getEntity($associatedEntity),
                ['VIEW', 'EDIT']
            )
        );
    }

    public function testVoteWhenAccessToAssociatedEntityDeniedBySomeAttribute(): void
    {
        $associatedEntity = new CmsOrganization();

        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($associatedEntity)],
                ['EDIT', self::identicalTo($associatedEntity)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote(
                $this->createMock(TokenInterface::class),
                $this->getEntity($associatedEntity),
                ['VIEW', 'EDIT', 'CREATE']
            )
        );
    }
}
