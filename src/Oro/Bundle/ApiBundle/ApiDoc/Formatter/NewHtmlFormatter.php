<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Formatter;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\DataTypes as ApiDocDataTypes;

/**
 * The HTML formatter that is used for new REST API views, e.g. "rest_json_api" and "rest_plain".
 */
class NewHtmlFormatter extends HtmlFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function renderOne(array $data)
    {
        return $this->engine->render('NelmioApiDocBundle::resource.html.twig', array_merge(
            [
                'data'           => $this->reformatData($data),
                'displayContent' => true,
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function render(array $collection)
    {
        return $this->engine->render('NelmioApiDocBundle::resources.html.twig', array_merge(
            [
                'resources' => $this->reformatDocData($collection),
                'actions'   => $this->getActions($collection)
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewName($name, $data, $parentName = null)
    {
        if ($parentName) {
            return sprintf('%s[%s]', $parentName, $name);
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    protected function compressNestedParameters(array $data, $parentName = null, $ignoreNestedReadOnly = false)
    {
        $newParams = parent::compressNestedParameters($data, $parentName, $ignoreNestedReadOnly);
        foreach ($data as $name => $info) {
            if (isset($info['actualType']) && ApiDocDataTypes::COLLECTION === $info['actualType']) {
                $newName = $this->getNewName($name, $info, $parentName);
                $newParams[$newName]['dataType'] = 'array of ' . $info['dataType'];
            }
        }

        return $newParams;
    }

    /**
     * Re-formats input and output data for a set of resource sections.
     *
     * @param array $collection
     *
     * @return array
     */
    protected function reformatDocData(array $collection)
    {
        foreach ($collection as $resourceBlockName => $resourceGroupBlock) {
            foreach ($resourceGroupBlock as $resourceUrl => $resourceBlock) {
                foreach ($resourceBlock as $resourceId => $resource) {
                    $collection[$resourceBlockName][$resourceUrl][$resourceId] = $this->reformatData($resource);
                }
            }
        }

        return $collection;
    }

    /**
     * Re-formats input and output data for collected resource.
     *
     * @param array $data
     *
     * @return array
     */
    protected function reformatData(array $data)
    {
        // reformat parameters (input data)
        if (array_key_exists('parameters', $data)) {
            $data['documentation'] .= $this->engine->render(
                'OroApiBundle:ApiDoc:input.html.twig',
                ['data' => $data['parameters']]
            );
            unset($data['parameters']);
        }

        // reformat output
        if (array_key_exists('response', $data)) {
            $data['documentation'] .= $this->engine->render(
                'OroApiBundle:ApiDoc:response.html.twig',
                ['data' => $data['response']]
            );
            $data['parsedResponseMap'] = [];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function processCollection(array $collection)
    {
        $array = [];
        foreach ($collection as $item) {
            /** @var ApiDoc $annotationObject */
            $annotationObject = $item['annotation'];
            $data = $annotationObject->toArray();
            if (!empty($item['action'])) {
                $data['action'] = $item['action'];
            }
            $array[$annotationObject->getSection()][$item['resource']][] = $data;
        }

        $processedCollection = [];
        foreach ($array as $section => $resources) {
            if (!$section) {
                $section = '_others';
            }
            foreach ($resources as $path => $annotations) {
                foreach ($annotations as $annotation) {
                    $processedCollection[$section][$path][] = $this->processAnnotation($annotation);
                }
            }
        }

        ksort($processedCollection);

        return $processedCollection;
    }

    /**
     * @param array $collection
     *
     * @return array [resource id => API action, ...]
     */
    protected function getActions(array $collection)
    {
        $actions = [];
        foreach ($collection as $resourceBlockName => $resourceGroupBlock) {
            foreach ($resourceGroupBlock as $resourceUrl => $resourceBlock) {
                foreach ($resourceBlock as $resource) {
                    if (!empty($resource['action'])) {
                        $actions[$resource['id']] = $resource['action'];
                    }
                }
            }
        }

        return $actions;
    }
}
