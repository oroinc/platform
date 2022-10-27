<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Model\Gender;
use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class GenderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var GenderProvider */
    private $genderProvider;

    /** @var array */
    private $expectedChoices = [
        'oro.user.gender.male.translated' => Gender::MALE,
        'oro.user.gender.female.translated' => Gender::FEMALE,
    ];

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.translated';
            });

        $this->genderProvider = new GenderProvider($translator);
    }

    public function testGetChoices()
    {
        // run two times to test internal cache
        $this->assertEquals($this->expectedChoices, $this->genderProvider->getChoices());
        $this->assertEquals($this->expectedChoices, $this->genderProvider->getChoices());
    }

    public function testGetLabelByName()
    {
        $expectedLabel = array_search(Gender::MALE, $this->expectedChoices, true);
        $this->assertEquals($expectedLabel, $this->genderProvider->getLabelByName(Gender::MALE));
    }

    public function testGetLabelByNameUnknownGender()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown gender with name "alien"');

        $this->genderProvider->getLabelByName('alien');
    }
}
