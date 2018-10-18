<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributeFormViewListener;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeGroupStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormView;

class AttributeFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $environment;

    /**
     * @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeManager;

    /**
     * @var AttributeFormViewListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new AttributeFormViewListener($this->attributeManager);
    }

    public function testOnEditWithoutFormRenderEvent()
    {
        $this->attributeManager
            ->expects($this->never())
            ->method('getGroupsWithAttributes');

        $this->listener->onEdit(new BeforeListRenderEvent($this->environment, new ScrollData(), new \stdClass()));
    }

    public function testOnViewWithoutViewRenderEvent()
    {
        $this->attributeManager
            ->expects($this->never())
            ->method('getGroupsWithAttributes');

        $this->listener->onViewList(new BeforeListRenderEvent($this->environment, new ScrollData(), new \stdClass()));
    }

    /**
     * @dataProvider formRenderDataProvider
     * @param array $groupsData
     * @param array $scrollData
     * @param string $templateHtml
     * @param array $expectedData
     * @param array $formViewChildren
     */
    public function testFormRender(
        array $groupsData,
        array $scrollData,
        $templateHtml,
        array $expectedData,
        array $formViewChildren
    ) {
        $formView = new FormView();
        $formView->children = $formViewChildren;

        $entity = $this->getEntity(TestActivityTarget::class, [
            'attributeFamily' => $this->getEntity(AttributeFamily::class)
        ]);

        $this->environment
            ->expects($templateHtml ? $this->once() : $this->never())
            ->method('render')
            ->willReturn($templateHtml);

        $this->attributeManager
            ->expects($this->once())
            ->method('getGroupsWithAttributes')
            ->willReturn($groupsData);

        $scrollData = new ScrollData($scrollData);
        $listEvent = new BeforeListRenderEvent($this->environment, $scrollData, $entity, $formView);
        $this->listener->onEdit($listEvent);

        $this->assertEquals($expectedData, $listEvent->getScrollData()->getData());
    }

    /**
     * @return array
     */
    public function formRenderDataProvider()
    {
        $data = $this->viewListDataProvider();

        //Add form view  parameters to data
        $data['empty group not added']['formViewChildren'] = [];
        $data['empty group gets deleted']['formViewChildren'] = [];
        $data['new group is added']['formViewChildren']['someField'] = new FormView();
        $data['invisible attribute not displayed']['formViewChildren']['someField'] = new FormView();
        $data['move attribute field to other group']['formViewChildren']['someField'] = (new FormView())->setRendered();

        return $data;
    }

    /**
     * @dataProvider viewListDataProvider
     * @param array $groupsData
     * @param array $scrollData
     * @param string $templateHtml
     * @param array $expectedData
     */
    public function testViewList(
        array $groupsData,
        array $scrollData,
        $templateHtml,
        array $expectedData
    ) {
        $entity = $this->getEntity(TestActivityTarget::class, [
            'attributeFamily' => $this->getEntity(AttributeFamily::class)
        ]);

        $this->environment
            ->expects($this->exactly((int)!empty($templateHtml)))
            ->method('render')
            ->willReturn($templateHtml);

        $this->attributeManager
            ->expects($this->once())
            ->method('getGroupsWithAttributes')
            ->willReturn($groupsData);

        $scrollData = new ScrollData($scrollData);
        $listEvent = new BeforeListRenderEvent($this->environment, $scrollData, $entity);
        $this->listener->onViewList($listEvent);

        $this->assertEquals($expectedData, $listEvent->getScrollData()->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function viewListDataProvider()
    {
        $label = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title']);
        $group1 = $this->getEntity(AttributeGroupStub::class, ['code' => 'group1', 'label' => $label]);
        $attributeVisible = $this->getEntity(
            FieldConfigModel::class,
            [
                'id' => 1,
                'fieldName' => 'someField',
                'data' => [
                    'view' => ['is_displayable' => true],
                    'form' => ['is_enabled' => true]
                ]
            ]
        );
        $attributeInvisible = $this->getEntity(
            FieldConfigModel::class,
            [
                'id' => 1,
                'fieldName' => 'someField',
                'data' => [
                    'view' => ['is_displayable' => false],
                    'form' => ['is_enabled' => false]
                ]
            ]
        );

        return [
            'empty group not added' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => []]
                ],
                'scrollData' => [],
                'templateHtml' => false,
                'expectedData' => [],
            ],
            'empty group gets deleted' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => []]
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'group1' => []
                    ]
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                    ]
                ],
            ],
            'new group is added' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$attributeVisible]]
                ],
                'scrollData' => [],
                'templateHtml' => 'field template',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'group1' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => ['someField' => 'field template']
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'invisible attribute not displayed' => [
                'groupsData' => [
                    [
                        'group' => $group1,
                        'attributes' => [
                            $attributeVisible,
                            $attributeInvisible
                        ]
                    ]
                ],
                'scrollData' => [],
                'templateHtml' => 'field template',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'group1' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => ['someField' => 'field template']
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'move attribute field to other group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$attributeVisible]]
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                        'otherField' => 'field template'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'otherField' => 'field template'
                                    ]
                                ]
                            ]
                        ],
                        'group1' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => ['someField' => 'field template']
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }
}
