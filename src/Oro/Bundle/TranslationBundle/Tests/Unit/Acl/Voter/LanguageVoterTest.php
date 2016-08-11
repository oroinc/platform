<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TranslationBundle\Acl\Voter\LanguageVoter;
use Oro\Bundle\TranslationBundle\Entity\Language;

use Oro\Component\Testing\Unit\EntityTrait;

class LanguageVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = Language::class;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LanguageVoter */
    protected $voter;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->any())
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

        $this->voter = new LanguageVoter($this->doctrineHelper, $this->configManager);
        $this->voter->setClassName(self::ENTITY_CLASS);
    }

    protected function tearDown()
    {
        unset($this->voter, $this->doctrineHelper, $this->configManager);
    }

    /**
     * @dataProvider voteDataProvider
     *
     * @param object $object
     * @param string $attribute
     * @param int $expected
     */
    public function testVote($object, $attribute, $expected)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_locale.language')
            ->willReturn('en');

        $this->assertEquals(
            $expected,
            $this->voter->vote($this->getMock(TokenInterface::class), $object, [$attribute])
        );
    }

    /**
     * @return array
     */
    public function voteDataProvider()
    {
        return [
            'abstain when not supported attribute' => [
                'object' => $this->getEntity(Language::class, ['id' => 42, 'code' => 'en']),
                'attribute' => 'TEST',
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
            'abstain when not supported class' => [
                'object' => $this->getEntity(Item::class, ['id' => 42]),
                'attribute' => 'EDIT',
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
            'abstain when new entity' => [
                'object' => $this->getEntity(Language::class),
                'attribute' => 'EDIT',
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
            'abstain when not default language' => [
                'object' => $this->getEntity(Language::class, ['id' => 42, 'code' => 'fr']),
                'attribute' => 'EDIT',
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
            'denied when default language' => [
                'object' => $this->getEntity(Language::class, ['id' => 42, 'code' => 'en']),
                'attribute' => 'EDIT',
                'expected' => VoterInterface::ACCESS_DENIED
            ]
        ];
    }
}
