<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Utils;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;
use Oro\Bundle\IntegrationBundle\Utils\FormUtils;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestIntegrationType;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestTwoWayConnector;

class EditModeUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function provideDataForIsEditAllowedTest()
    {
        return [
            [Channel::EDIT_MODE_ALLOW, true],
            [Channel::EDIT_MODE_FORCED_ALLOW, true],
            [Channel::EDIT_MODE_RESTRICTED, true],
            [Channel::EDIT_MODE_DISALLOW, false],
            [Channel::EDIT_MODE_FORCED_DISALLOW, false],
        ];
    }

    public function provideDataForAttemptChangeEditModeTest()
    {
        return [
            [Channel::EDIT_MODE_ALLOW, 'aNewStatus'],
            [Channel::EDIT_MODE_FORCED_ALLOW, Channel::EDIT_MODE_FORCED_ALLOW],
            [Channel::EDIT_MODE_RESTRICTED, 'aNewStatus'],
            [Channel::EDIT_MODE_DISALLOW, 'aNewStatus'],
            [Channel::EDIT_MODE_FORCED_DISALLOW, Channel::EDIT_MODE_FORCED_DISALLOW],
        ];
    }

    /**
     * @dataProvider provideDataForIsEditAllowedTest
     */
    public function testIsEditAllowReturnExpectedResult($editMode, $expected)
    {
        $this->assertSame($expected, EditModeUtils::isEditAllowed($editMode));
    }

    /**
     * @dataProvider provideDataForAttemptChangeEditModeTest
     */
    public function testAttemptChangeEditModeWorksCorrectly($current, $expected)
    {
        $channel = new Channel();
        $channel->setEditMode($current);

        EditModeUtils::attemptChangeEditMode($channel, 'aNewStatus');

        $this->assertSame($expected, $channel->getEditMode());
    }
}
