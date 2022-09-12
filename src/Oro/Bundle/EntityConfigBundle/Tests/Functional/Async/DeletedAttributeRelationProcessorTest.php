<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\Async\DeletedAttributeRelationProcessor;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DeletedAttributeRelationProcessorTest extends WebTestCase
{
    private EntityManagerInterface $testActivityTargetManager;

    private DeletedAttributeRelationProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadAttributeFamilyData::class,
            LoadActivityTargets::class,
        ]);

        $this->processor = self::getContainer()->get('oro_entity_config.async.deleted_attribute_relation');
        $this->testActivityTargetManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(TestActivityTarget::class);
    }

    public function testProcess(): void
    {
        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $testActivityTarget = $this->loadTestActivityTarget($attributeFamily);

        self::assertEquals('foo', $testActivityTarget->getRegularAttribute1());
        self::assertEquals('bar', $testActivityTarget->getRegularAttribute2());

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody([
            'attributeFamilyId' => $attributeFamily->getId(),
            'attributeNames' => [
                LoadAttributeData::REGULAR_ATTRIBUTE_1,
                LoadAttributeData::REGULAR_ATTRIBUTE_2,
            ],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertSame(MessageProcessorInterface::ACK, $result);

        $this->testActivityTargetManager->refresh($testActivityTarget);

        self::assertNull($testActivityTarget->getRegularAttribute1());
        self::assertNull($testActivityTarget->getRegularAttribute2());
    }

    private function loadTestActivityTarget(AttributeFamily $attributeFamily): TestActivityTarget
    {
        $testActivityTarget = $this->getReference('activity_target_one');
        $testActivityTarget->setRegularAttribute1('foo');
        $testActivityTarget->setRegularAttribute2('bar');
        $testActivityTarget->setAttributeFamily($attributeFamily);

        $this->testActivityTargetManager->persist($testActivityTarget);
        $this->testActivityTargetManager->flush();

        return $testActivityTarget;
    }
}
