# Return Custom Status Code?

To return custom status code from the layout, create and return
`Symfony\Component\HttpFoundation\Response` passing rendered layout content as the first argument, and the status code as the second.

Study the example below:

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

Please be aware that if you pass some custom context params to the `LayoutManager::render()` method as the first argument, you have to resolve these variables by passing keys as the second argument. 

You could have gained the similar result by configuring the layout via the `@Layoutvars={"some_context_variable"})` annotations. However, annotations cannot be used when you call the `LayoutManager::render()` manually.