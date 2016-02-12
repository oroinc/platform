<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\TagBundle\Converter\TemplateFixtureTaggableRelationCalculator;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\UserBundle\Entity\User;

class TemplateFixtureTaggableRelationCalculatorTest extends \PHPUnit_Framework_TestCase
{
    protected $templateManager;
    protected $calculator;

    public function setUp()
    {
        $this->templateManager = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->calculator = new TemplateFixtureTaggableRelationCalculator($this->templateManager);
    }

    /**
     * @dataProvider maxRelatedEntitiesProvider
     */
    public function testGetMaxRelatedEntities(array $users, $maxTagsCount)
    {
        $templateFixture = $this->getMock('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface');
        $templateFixture->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(new ArrayCollection($users)));

        $this->templateManager->expects($this->any())
            ->method('getEntityFixture')
            ->with('Oro\Bundle\UserBundle\Entity\User')
            ->will($this->returnValue($templateFixture));

        $actual = $this->calculator->getMaxRelatedEntities('Oro\Bundle\UserBundle\Entity\User', 'tags');
        $this->assertEquals($maxTagsCount, $actual);
    }
    
    public function maxRelatedEntitiesProvider()
    {
        return [
            [
                'users' => [],
                'maxTagsCount' => 0,
            ],
            [
                'users' => [
                    $this->createUserWithNTags(0),
                ],
                'maxTagsCount' => 0,
            ],
            [
                'users' => [
                    $this->createUserWithNTags(4),
                ],
                'maxTagsCount' => 4,
            ],
            [
                'users' => [
                    $this->createUserWithNTags(0),
                    $this->createUserWithNTags(5),
                    $this->createUserWithNTags(3),
                ],
                'maxTagsCount' => 5,
            ],
        ];
    }

    /**
     * @param int $n
     *
     * @return User
     */
    protected function createUserWithNTags($n)
    {
        $user = new User();

        $tags = [
            'autocomplete' => [],
            'all' => [],
            'owner' => [],
        ];

        for ($i = 0; $i < $n; $i++) {
            $tags['all'][] = new Tag('tag_' . $i);
        }

        if ($tags['all']) {
            $user->setTags($tags);
        }

        return $user;
    }
}
