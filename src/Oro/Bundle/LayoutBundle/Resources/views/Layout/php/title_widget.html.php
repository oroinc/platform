<?php //@codingStandardsIgnoreFile ?>
<?php if ($reverse) { $value = array_reverse($value); } ?>
<?php $keys = array_keys($value); ?>
<?php $lastIndex = end($keys); ?>

<title><?php foreach ($value as $index => $element): ?><?php echo $view->escape($view['layout']->text($element, $translation_domain)) ?><?php if ($index !== $lastIndex) { echo $separator; } ?><?php endforeach; ?></title>
