<?php //@codingStandardsIgnoreFile ?>
<?php foreach ($attr as $k => $v): ?>
<?php printf('%s="%s" ', $view->escape($k), $view->escape($k === 'title' ? $view['layout']->text($v, $translation_domain) : $v)) ?>
<?php endforeach; ?>
