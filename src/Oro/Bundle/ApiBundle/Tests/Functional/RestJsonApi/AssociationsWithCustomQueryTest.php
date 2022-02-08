<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class AssociationsWithCustomQueryTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_custom_magazine.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'testapicustommagazines']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'testapicustommagazines',
                        'id'            => '<toString(@magazine1->id)>',
                        'relationships' => [
                            'customArticles'    => [
                                'data' => [
                                    ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>'],
                                    ['type' => 'testapicustomarticles', 'id' => '<toString(@article2->id)>'],
                                    ['type' => 'testapicustomarticles', 'id' => '<toString(@article3->id)>']
                                ]
                            ],
                            'customBestArticle' => [
                                'data' => ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testapicustommagazines',
                        'id'            => '<toString(@magazine2->id)>',
                        'relationships' => [
                            'customArticles'    => [
                                'data' => []
                            ],
                            'customBestArticle' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(['entity' => 'testapicustommagazines', 'id' => '<toString(@magazine1->id)>']);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapicustommagazines',
                    'id'            => '<toString(@magazine1->id)>',
                    'relationships' => [
                        'customArticles'    => [
                            'data' => [
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>'],
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article2->id)>'],
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article3->id)>']
                            ]
                        ],
                        'customBestArticle' => [
                            'data' => ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithIncludeToManyAssociation()
    {
        $response = $this->get(
            ['entity' => 'testapicustommagazines', 'id' => '<toString(@magazine1->id)>'],
            ['include' => 'customArticles']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapicustommagazines',
                    'id'            => '<toString(@magazine1->id)>',
                    'relationships' => [
                        'customArticles'    => [
                            'data' => [
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>'],
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article2->id)>'],
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article3->id)>']
                            ]
                        ],
                        'customBestArticle' => [
                            'data' => ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapicustomarticles',
                        'id'         => '<toString(@article1->id)>',
                        'attributes' => ['headline' => 'Article 1']
                    ],
                    [
                        'type'       => 'testapicustomarticles',
                        'id'         => '<toString(@article2->id)>',
                        'attributes' => ['headline' => 'Article 2']
                    ],
                    [
                        'type'       => 'testapicustomarticles',
                        'id'         => '<toString(@article3->id)>',
                        'attributes' => ['headline' => 'Article 3']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithIncludeToOneAssociation()
    {
        $response = $this->get(
            ['entity' => 'testapicustommagazines', 'id' => '<toString(@magazine1->id)>'],
            ['include' => 'customBestArticle']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapicustommagazines',
                    'id'            => '<toString(@magazine1->id)>',
                    'relationships' => [
                        'customArticles'    => [
                            'data' => [
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>'],
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article2->id)>'],
                                ['type' => 'testapicustomarticles', 'id' => '<toString(@article3->id)>']
                            ]
                        ],
                        'customBestArticle' => [
                            'data' => ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapicustomarticles',
                        'id'         => '<toString(@article1->id)>',
                        'attributes' => ['headline' => 'Article 1']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToManyAssociation()
    {
        $response = $this->getSubresource([
            'entity'      => 'testapicustommagazines',
            'id'          => '@magazine1->id',
            'association' => 'customArticles'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapicustomarticles',
                        'id'         => '<toString(@article1->id)>',
                        'attributes' => ['headline' => 'Article 1']
                    ],
                    [
                        'type'       => 'testapicustomarticles',
                        'id'         => '<toString(@article2->id)>',
                        'attributes' => ['headline' => 'Article 2']
                    ],
                    [
                        'type'       => 'testapicustomarticles',
                        'id'         => '<toString(@article3->id)>',
                        'attributes' => ['headline' => 'Article 3']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToOneAssociation()
    {
        $response = $this->getSubresource([
            'entity'      => 'testapicustommagazines',
            'id'          => '@magazine1->id',
            'association' => 'customBestArticle'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapicustomarticles',
                    'id'         => '<toString(@article1->id)>',
                    'attributes' => ['headline' => 'Article 1']
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForToManyAssociation()
    {
        $response = $this->getRelationship([
            'entity'      => 'testapicustommagazines',
            'id'          => '@magazine1->id',
            'association' => 'customArticles'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>'],
                    ['type' => 'testapicustomarticles', 'id' => '<toString(@article2->id)>'],
                    ['type' => 'testapicustomarticles', 'id' => '<toString(@article3->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForToOneAssociation()
    {
        $response = $this->getRelationship([
            'entity'      => 'testapicustommagazines',
            'id'          => '@magazine1->id',
            'association' => 'customBestArticle'
        ]);

        $this->assertResponseContains(
            [
                'data' => ['type' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>']
            ],
            $response
        );
    }
}
