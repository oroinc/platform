<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Utils;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;
use PHPUnit\Framework\TestCase;

class EditModeUtilsTest extends TestCase
{
    public function provideDataForIsEditAllowedTest()
    {
        return [
            [Channel::EDIT_MODE_ALLOW, true],
            [Channel::EDIT_MODE_RESTRICTED, false],
            [Channel::EDIT_MODE_DISALLOW, false],
        ];
    }

    public function provideDataForAttemptChangeEditModeTest()
    {
        return [
            [Channel::EDIT_MODE_ALLOW, Channel::EDIT_MODE_DISALLOW, Channel::EDIT_MODE_DISALLOW],
            [Channel::EDIT_MODE_RESTRICTED, Channel::EDIT_MODE_ALLOW, Channel::EDIT_MODE_ALLOW],
            [Channel::EDIT_MODE_DISALLOW, Channel::EDIT_MODE_ALLOW, Channel::EDIT_MODE_ALLOW],
            [0, Channel::EDIT_MODE_ALLOW, 0],
        ];
    }

    public function isSwitchEnableAllowedDataProvider(): array
    {
        return [
            [Channel::EDIT_MODE_ALLOW, true],
            [Channel::EDIT_MODE_RESTRICTED, true],
            [Channel::EDIT_MODE_DISALLOW, false],
        ];
    }

    /**
     * @dataProvider provideDataForIsEditAllowedTest
     */
    public function testIsEditAllowReturnExpectedResult($editMode, $expected): void
    {
        $this->assertSame($expected, EditModeUtils::isEditAllowed($editMode));
    }

    /**
     * @dataProvider provideDataForAttemptChangeEditModeTest
     */
    public function testAttemptChangeEditModeWorksCorrectly($current, $newEditMode, $expected): void
    {
        $channel = new Channel();
        $channel->setEditMode($current);

        EditModeUtils::attemptChangeEditMode($channel, $newEditMode);

        $this->assertSame($expected, $channel->getEditMode());
    }

    /**
     * @dataProvider isSwitchEnableAllowedDataProvider
     */
    public function testIsSwitchEnableAllowed($mode, $expected): void
    {
        $this->assertEquals($expected, EditModeUtils::isSwitchEnableAllowed($mode));
    }
}
