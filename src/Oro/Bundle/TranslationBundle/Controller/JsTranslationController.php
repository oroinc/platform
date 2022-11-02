<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Oro\Bundle\TranslationBundle\Provider\JsTranslationGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used when JSON file with JS translations not generated and application make direct request to get this file.
 */
class JsTranslationController
{
    private JsTranslationGenerator $generator;

    public function __construct(JsTranslationGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function indexAction(Request $request, string $_locale): Response
    {
        return new Response(
            $this->generator->generateJsTranslations($_locale),
            200,
            ['Content-Type' => $request->getMimeType('json')]
        );
    }
}
