<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Manager\MessageManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class MessageManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $navigation;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $storage;

    /**
     * @var MessageManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->session = new Session(new MockArraySessionStorage());

        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id, array $parameters = []) {
                        return str_replace(array_keys($parameters), array_values($parameters), $id . '.trans');
                    }
                )
            );
        $this->translator->expects($this->any())
            ->method('transChoice')
            ->will(
                $this->returnCallback(
                    function ($id, $count, array $parameters = []) {
                        return str_replace(
                            array_keys($parameters),
                            array_values($parameters),
                            $id . '.trans.' . $count
                        );
                    }
                )
            );

        $this->navigation =
            $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation')
                ->disableOriginalConstructor()
                ->getMock();

        $this->storage = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new MessageManager($this->session, $this->translator, $this->navigation, $this->storage);
    }

    public function testAddFlashMessage()
    {
        $type = 'test_type';
        $message = 'Test Message';

        $this->assertEmpty($this->session->getFlashBag()->all());
        $this->manager->addFlashMessage($type, $message);
        $this->assertEquals([$type => [$message]], $this->session->getFlashBag()->all());
    }

    /**
     * @param string $expected
     * @param string $scope
     * @param int|null $count
     * @dataProvider getNotAvailableMessageDataProvider
     */
    public function testGetNotAvailableMessage($expected, $scope, $count = null)
    {
        $entity = new \stdClass();

        $this->navigation->expects($this->once())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->will($this->returnValue($count));

        $this->assertEquals($expected, $this->manager->getNotAvailableMessage($entity, $scope));
    }

    /**
     * @return array
     */
    public function getNotAvailableMessageDataProvider()
    {
        return [
            'no count' => [
                'expected' => 'oro.entity_pagination.message.not_available.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
            ],
            'view with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_available.trans ' .
                    'oro.entity_pagination.message.stats_number_view_12_record|stats_number_view_12_records.trans.12',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'count' => 12,
            ],
            'edit with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_available.trans ' .
                    'oro.entity_pagination.message.stats_number_edit_23_record|stats_number_edit_23_records.trans.23',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'count' => 23,
            ],
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Scope "invalid" is not available.
     */
    public function testGetStatsMessageForInvalidScope()
    {
        $entity = new \stdClass();
        $scope = 'invalid';

        $this->navigation->expects($this->once())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->will($this->returnValue(1));

        $this->manager->getNotAvailableMessage($entity, $scope);
    }

    /**
     * @param string $expected
     * @param string $scope
     * @param int|null $count
     * @dataProvider getNotAccessibleMessageDataProvider
     */
    public function testGetNotAccessibleMessage($expected, $scope, $count = null)
    {
        $entity = new \stdClass();

        $this->navigation->expects($this->once())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->will($this->returnValue($count));

        $this->assertEquals($expected, $this->manager->getNotAccessibleMessage($entity, $scope));
    }

    /**
     * @return array
     */
    public function getNotAccessibleMessageDataProvider()
    {
        return [
            'no count' => [
                'expected' => 'oro.entity_pagination.message.not_accessible.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
            ],
            'view with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_accessible.trans ' .
                    'oro.entity_pagination.message.stats_number_view_12_record|stats_number_view_12_records.trans.12',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'count' => 12,
            ],
            'edit with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_accessible.trans ' .
                    'oro.entity_pagination.message.stats_number_edit_23_record|stats_number_edit_23_records.trans.23',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'count' => 23,
            ],
        ];
    }

    /**
     * @param string|null $expected
     * @param string $scope
     * @param bool $shown
     * @param int|null $viewCount
     * @param int|null $editCount
     * @dataProvider getInfoMessageDataProvider
     */
    public function testGetInfoMessage($expected, $scope, $shown, $viewCount = null, $editCount = null)
    {
        $entity = new \stdClass();
        $entityName = get_class($entity);

        $this->storage->expects($this->once())
            ->method('isInfoMessageShown')
            ->with($entityName, $scope)
            ->will($this->returnValue($shown));

        $this->navigation->expects($this->any())
            ->method('getTotalCount')
            ->with($entity, $this->isType('string'))
            ->will(
                $this->returnValueMap(
                    [
                        [$entity, EntityPaginationManager::VIEW_SCOPE, $viewCount],
                        [$entity, EntityPaginationManager::EDIT_SCOPE, $editCount],
                    ]
                )
            );

        if ($expected) {
            $this->storage->expects($this->once())
                ->method('setInfoMessageShown')
                ->with($entityName, $scope);
        } else {
            $this->storage->expects($this->never())
                ->method('setInfoMessageShown');
        }

        $this->assertSame($expected, $this->manager->getInfoMessage($entity, $scope));
    }

    /**
     * @return array
     */
    public function getInfoMessageDataProvider()
    {
        return [
            'message already shown' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => true,
            ],
            'no entities is storage' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => null
            ],
            'only view' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 10
            ],
            'only edit' => [
                'expected' => null,
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => null,
                'editCount' => 10,
            ],
            'view to edit equals view scope' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 10,
            ],
            'view to edit equals edit scope' => [
                'expected' => null,
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 10,
            ],
            'edit to view increased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_number_view_5_record|stats_number_view_5_records.trans.5',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 5,
                'editCount' => 10,
            ],
            'edit to view decreased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_number_view_10_record|stats_number_view_10_records.trans.10',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 5,
            ],
            'view to edit increased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_number_edit_10_record|stats_number_edit_10_records.trans.10',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => 5,
                'editCount' => 10,
            ],
            'view to edit decreased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_changed_view_to_edit.trans ' .
                    'oro.entity_pagination.message.stats_number_edit_5_record|stats_number_edit_5_records.trans.5',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 5,
            ],
        ];
    }
}
