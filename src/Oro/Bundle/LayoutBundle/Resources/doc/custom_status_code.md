How to return custom status code?
===============
You may need to return custom status code from layout. For this you have to create and return
`Symfony\Component\HttpFoundation\Response` passing rendered layout content as first argument and status code as second.
Have a look on sample below:

```php
     /**
     * @Route("/sample_not_found_page_code")
     *
     * @return Response    
     */
    public function sampleNotFoundCodeAction()
    { 
        $contextParams = ['some_context_variable' => 'value'];
        $content = $this->get('layout')->render($contextParams, ['some_context_variable']);

        return new Response($content, 404);
    }
```

Please pay attention if you pass some custom context params as first argument to `LayoutManager::render()` method you
have to resolve these variables passing keys as a second argument. In fact it is similar(alternative) action for
configuring layout via annotations `@Layout(vars={"some_context_variable"})`. Which can not be used when you call
`LayoutManager::render()` manually.