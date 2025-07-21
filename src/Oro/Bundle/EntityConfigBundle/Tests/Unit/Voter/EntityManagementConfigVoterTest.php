<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Voter;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\TestEntity;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityManagementConfig;
use Oro\Bundle\EntityConfigBundle\Voter\EntityManagementConfigVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EntityManagementConfigVoterTest extends TestCase
{
    private EntityManagementConfigVoter $voter;
    private TokenInterface $token;

    #[\Override]
    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->voter = new EntityManagementConfigVoter();
    }

    /** @dataProvider voteDataProvider */
    public function testVote(object $subject, int $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->voter->vote($this->token, $subject, []));
    }

    public function voteDataProvider(): array
    {
        $notManageableEntityConfigModel = new EntityConfigModel(TestEntity::class);
        $notManageableEntityConfigModel->fromArray(EntityManagementConfig::SECTION, ['enabled' => false]);

        $manageableEntityConfigModel = new EntityConfigModel(TestEntity::class);
        $manageableEntityConfigModel->fromArray(EntityManagementConfig::SECTION, ['enabled' => true]);

        $manageableFieldConfigModel = new FieldConfigModel('testField', StringType::getType(Type::STRING));
        $manageableFieldConfigModel->setEntity($manageableEntityConfigModel);

        $notManageableFieldConfigModel = new FieldConfigModel('testField', StringType::getType(Type::STRING));
        $notManageableFieldConfigModel->setEntity($notManageableEntityConfigModel);

        $fieldConfigModelWithoutEntity = new FieldConfigModel('testField', StringType::getType(Type::STRING));

        return [
            'unsupported subject' => [
                'subject' => new \stdClass(),
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
            'manageable entity config model' => [
                'subject' => $manageableEntityConfigModel,
                'expected' => VoterInterface::ACCESS_GRANTED
            ],
            'not manageable entity config model' => [
                'subject' => $notManageableEntityConfigModel,
                'expected' => VoterInterface::ACCESS_DENIED
            ],
            'field config model without entity model' => [
                'subject' => $fieldConfigModelWithoutEntity,
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
            'field config model with manageable entity model' => [
                'subject' => $manageableFieldConfigModel,
                'expected' => VoterInterface::ACCESS_GRANTED
            ],
            'field config model with not manageable entity model' => [
                'subject' => $notManageableFieldConfigModel,
                'expected' => VoterInterface::ACCESS_DENIED
            ],
        ];
    }
}
