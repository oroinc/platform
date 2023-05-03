<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Layout;

use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\LayoutBundle\Tests\Functional\LayoutTestCase;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use stdClass;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @covers \Oro\Bundle\LayoutBundle\Layout\CacheTwigRendererDecorator::searchAndRenderBlock
 * @covers \Oro\Bundle\LayoutBundle\Layout\CacheLayoutBuilder
 */
class CacheRendererTest extends LayoutTestCase
{
    private const BLOCK_THEME = '@OroLayoutBundleStub/templates/test_block_theme.html.twig';
    private const MAX_AGE = 2;

    /**
     * @var LayoutManager
     */
    private $layoutManager;

    /**
     * @var LayoutContext
     */
    private $context;

    /**
     * @var TagAwareAdapterInterface
     */
    private $cache;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->context = new LayoutContext(['theme' => 'default', 'widget_container' => 'non-existent']);
        $this->layoutManager = $this->getContainer()->get('oro_layout.layout_manager');
        $this->cache = $this->getContainer()->get('cache.oro_layout.render');
        $this->cache->clear();
    }

    /**
     * @dataProvider notCachedBlocksProvider
     * @param bool|array $cache
     */
    public function testNotCachedBlocks($cache): void
    {
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:non_cached_blocks'.sha1(serialize($cache)));

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add('dynamic', 'root', 'text', ['text' => '=data["text"]', 'cache' => $cache])
            ->add('static', 'root', 'text', ['text' => 'STATIC TEXT.'])
            ->getLayout($this->context);

        // render
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT.');
        $this->assertHtmlEquals('UPDATED TEXT.STATIC TEXT.', $this->renderLayout());
    }

    public function notCachedBlocksProvider(): array
    {
        return [
            ['cache' => false],
            ['cache' => null],
            ['cache' => ['maxAge' => 0]],
            [
                'cache' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 0,
                ],
            ],
            [
                'cache' => [
                    'if' => false,
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 900,
                ],
            ],
            [
                'cache' => [
                    'if' => null,
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 900,
                ],
            ],
            [
                'cache' => [
                    'if' => '=!data.offsetExists("text")',
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider cachedBlocksProvider
     * @param bool|array $cache
     */
    public function testCachedBlocks($cache): void
    {
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:cached_blocks'.sha1(serialize($cache)));

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add('dynamic', 'root', 'text', ['text' => '=data["text"]', 'cache' => $cache])
            ->add('static', 'root', 'text', ['text' => 'STATIC TEXT.'])
            ->getLayout($this->context);

        // render
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT.');
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());
    }

    public function cachedBlocksProvider(): array
    {
        return [
            ['cache' => true],
            ['cache' => ['maxAge' => 900]],
            [
                'cache' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                ],
            ],
            [
                'cache' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 900,
                ],
            ],
            [
                'cache' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => null,
                ],
            ],
            [
                'cache' => [
                    'if' => true,
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => null,
                ],
            ],
            [
                'cache' => [
                    'if' => '=data.offsetExists("text")',
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => null,
                ],
            ],
        ];
    }

    public function testPostCacheSubstitution(): void
    {
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:post_cache_substitution');

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add('first_level', 'root', 'container', ['cache' => true])
            ->add(
                'second_level_not_cached_1',
                'first_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->add(
                'second_level_not_cached_2',
                'first_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->add('second_level_cached', 'first_level', 'text', ['text' => '=data["text"]', 'cache' => true])
            ->add('second_level', 'first_level', 'container', ['cache' => false])
            ->add('third_level', 'second_level', 'container', ['cache' => true])
            ->add('fourth_level', 'third_level', 'container', ['cache' => false])
            ->add('fifth_level_regular', 'fourth_level', 'text', ['text' => '=data["text"]'])
            ->add(
                'fifth_level_not_cached_1',
                'fourth_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->add('fifth_level_cached', 'fourth_level', 'text', ['text' => '=data["text"]', 'cache' => true])
            ->add(
                'fifth_level_not_cached_2',
                'fourth_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->getLayout($this->context);

        // render
        $this->assertHtmlEquals(
            '
<first_level>
ORIGINAL TEXT.ORIGINAL TEXT.ORIGINAL TEXT.<second_level>
<third_level>
<fourth_level>
ORIGINAL TEXT.ORIGINAL TEXT.ORIGINAL TEXT.ORIGINAL TEXT.
</fourth_level>
</third_level>
</second_level>
</first_level>
',
            $this->renderLayout()
        );

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT.');
        $this->assertHtmlEquals(
            '
<first_level>
UPDATED TEXT.UPDATED TEXT.ORIGINAL TEXT.<second_level>
<third_level>
<fourth_level>
ORIGINAL TEXT.UPDATED TEXT.ORIGINAL TEXT.UPDATED TEXT.
</fourth_level>
</third_level>
</second_level>
</first_level>
',
            $this->renderLayout()
        );
    }

    public function testVaryBy(): void
    {
        $this->context->data()->set('first', 'FIRST ORIGINAL.');
        $this->context->data()->set('second', 'SECOND ORIGINAL.');
        $this->context->data()->set('third', 'THIRD ORIGINAL.');
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:vary_by');

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add(
                'ordered_list',
                'root',
                'block',
                [
                    'vars' => [
                        'first' => '=data["first"]',
                        'second' => '=data["second"]',
                        'third' => '=data["third"]',
                    ],
                    'cache' => [
                        'varyBy' => [
                            'first' => '=data["first"]',
                        ],
                    ],
                ]
            )
            ->getLayout($this->context);

        // render
        $this->assertHtmlEquals(
            '
<ol>
<li>
FIRST ORIGINAL.</li>
<li>
SECOND ORIGINAL.</li>
<li>
THIRD ORIGINAL.</li>
</ol>
',
            $this->renderLayout()
        );
        // render with updated data, except varyBy
        $this->context->data()->set('second', 'SECOND UPDATED.');
        $this->context->data()->set('third', 'THIRD UPDATED.');
        $this->assertHtmlEquals(
            '
<ol>
<li>
FIRST ORIGINAL.</li>
<li>
SECOND ORIGINAL.</li>
<li>
THIRD ORIGINAL.</li>
</ol>
',
            $this->renderLayout()
        );

        // render with updated varyBy data
        $this->context->data()->set('first', 'FIRST UPDATED.');
        $this->assertHtmlEquals(
            '
<ol>
<li>
FIRST UPDATED.</li>
<li>
SECOND UPDATED.</li>
<li>
THIRD UPDATED.</li>
</ol>
',
            $this->renderLayout()
        );
    }

    public function testTags(): void
    {
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:tags');

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add(
                'dynamic',
                'root',
                'text',
                [
                    'text' => '=data["text"]',
                    'cache' => [
                        'tags' => [
                            '="text_" ~data["text"]',
                        ],
                    ],
                ]
            )
            ->add('static', 'root', 'text', ['text' => 'STATIC TEXT.'])
            ->getLayout($this->context);

        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT.');
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        $this->cache->invalidateTags(['text_ORIGINAL TEXT.']);

        // render after tags invalidation
        $this->context->data()->set('text', 'UPDATED TEXT.');
        $this->assertHtmlEquals('UPDATED TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated data, block still cached after tags invalidation
        $this->context->data()->set('text', 'UPDATED 2 TEXT.');
        $this->assertHtmlEquals('UPDATED TEXT.STATIC TEXT.', $this->renderLayout());
    }

    public function testMaxAge(): void
    {
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        $this->context->data()->set('visible', true);
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:max_age'.rand());

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add(
                'dynamic',
                'root',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => self::MAX_AGE]]
            )
            ->add('static', 'root', 'text', ['text' => 'STATIC TEXT.'])
            ->getLayout($this->context);

        // render
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT.');
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        // render after expired max age
        sleep(self::MAX_AGE + 1);
        $this->assertHtmlEquals('UPDATED TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT 2.');
        $this->assertHtmlEquals('UPDATED TEXT.STATIC TEXT.', $this->renderLayout());
    }

    public function testVisibleIsNotCached(): void
    {
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        $this->context->data()->set('visible', true);
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:visible_is_not_cached');

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add(
                'dynamic',
                'root',
                'text',
                ['text' => '=data["text"]', 'visible' => '=data["visible"]', 'cache' => true]
            )
            ->add('static', 'root', 'text', ['text' => 'STATIC TEXT.'])
            ->getLayout($this->context);

        // render
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated visibility
        $this->context->data()->set('visible', false);
        $this->assertHtmlEquals('STATIC TEXT.', $this->renderLayout());

        // render with updated visibility
        $this->context->data()->set('visible', true);
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());
    }

    /**
     * @dataProvider invalidCacheMetadataProvider
     * @param mixed $cache
     */
    public function testInvalidCacheMetadata($cache): void
    {
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        $this->context->data()->set('object', new stdClass());
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:invalid_cache_metadata'.sha1(serialize($cache).rand()));

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add('dynamic', 'root', 'text', ['text' => '=data["text"]', 'cache' => $cache])
            ->add('static', 'root', 'text', ['text' => 'STATIC TEXT.'])
            ->getLayout($this->context);

        // render
        $this->assertHtmlEquals('ORIGINAL TEXT.STATIC TEXT.', $this->renderLayout());

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT.');
        $this->assertHtmlEquals('UPDATED TEXT.STATIC TEXT.', $this->renderLayout());
    }

    public function invalidCacheMetadataProvider(): array
    {
        return [
            ['cache' => 'string value'],
            ['cache' => 1],
            ['cache' => ['varyBy' => ['object' => '=data["object"]']]],
            ['cache' => ['tags' => ['=data["object"]']]],
        ];
    }

    public function testCacheInvalidationDuringRequest()
    {
        $this->cache->clear();
        $this->context->data()->set('text', 'ORIGINAL TEXT.');
        // layout context must be unique for each test, otherwise layout tree is cached and new options are not applied
        $this->context->set('route_name', 'layout_cache:invalidation_during_request'.rand(0, 4903943094));

        $this->layoutManager->getLayoutBuilder()
            ->add('root', null, 'container')
            ->add('first_level', 'root', 'container', ['cache' => true])
            ->add(
                'second_level_not_cached_1',
                'first_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->add(
                'second_level_not_cached_2',
                'first_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->add('second_level_cached', 'first_level', 'text', ['text' => '=data["text"]', 'cache' => true])
            ->add('second_level', 'first_level', 'container')
            ->add('third_level', 'second_level', 'container')
            ->add('fourth_level', 'third_level', 'container')
            ->add('fifth_level_regular', 'fourth_level', 'text', ['text' => '=data["text"]'])
            ->add(
                'fifth_level_not_cached_1',
                'fourth_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->add('fifth_level_cached', 'fourth_level', 'text', ['text' => '=data["text"]', 'cache' => true])
            ->add(
                'fifth_level_not_cached_2',
                'fourth_level',
                'text',
                ['text' => '=data["text"]', 'cache' => ['maxAge' => 0]]
            )
            ->getLayout($this->context);

        // render with the empty cache
        $this->assertHtmlEquals(
            '
<first_level>
ORIGINAL TEXT.ORIGINAL TEXT.ORIGINAL TEXT.<second_level>
<third_level>
<fourth_level>
ORIGINAL TEXT.ORIGINAL TEXT.ORIGINAL TEXT.ORIGINAL TEXT.
</fourth_level>
</third_level>
</second_level>
</first_level>
',
            $this->renderLayout()
        );

        // render with updated data
        $this->context->data()->set('text', 'UPDATED TEXT.');

        $layout = $this->buildLayout();
        // clear the cache after the layout is build, and before it's rendered in the same request
        $this->cache->clear();
        $this->assertHtmlEquals(
            '
<first_level>
UPDATED TEXT.UPDATED TEXT.UPDATED TEXT.<second_level>
<third_level>
<fourth_level>
ORIGINAL TEXT.UPDATED TEXT.UPDATED TEXT.UPDATED TEXT.
</fourth_level>
</third_level>
</second_level>
</first_level>
',
            $layout->render()
        );

        // render one more time with updated data and fresh cache
        $this->assertHtmlEquals(
            '
<first_level>
UPDATED TEXT.UPDATED TEXT.UPDATED TEXT.<second_level>
<third_level>
<fourth_level>
UPDATED TEXT.UPDATED TEXT.UPDATED TEXT.UPDATED TEXT.
</fourth_level>
</third_level>
</second_level>
</first_level>
',
            $this->renderLayout()
        );
    }

    private function buildLayout(): Layout
    {
        return $this->layoutManager->getLayoutBuilder()
            // no need to add all the blocks again, because the layout tree is cached by context
            ->add('root', null, 'container')
            ->getLayout($this->context)
            ->setBlockTheme(self::BLOCK_THEME);
    }

    private function renderLayout(): string
    {
        $layout = $this->buildLayout();

        return $layout->render();
    }
}
