<?php //@codingStandardsIgnoreFile ?>
<?php if (isset($attr['class'])) $attr['class'] = str_replace('{{ class_prefix }}', $class_prefix, $attr['class']); ?>
<?php foreach ($attr as $k => $v): ?>
    <?php if ($v instanceof Traversable || is_array($v)) $v = json_encode($v, JSON_FORCE_OBJECT); ?>
    <?php printf('%s="%s" ', $view->escape($k), $view->escape($k === 'title' ? $view['layout']->text($v, $translation_domain) : $v)) ?>
<?php endforeach; ?>
