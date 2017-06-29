<?php //@codingStandardsIgnoreFile?>
<?php if ($type === 'input'): ?>
<?php echo $view['layout']->block($block, 'button_widget_input') ?>
<?php else: ?>
<?php echo $view['layout']->block($block, 'button_widget_button') ?>
<?php endif ?>
