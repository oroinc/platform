<?php //@codingStandardsIgnoreFile ?>
<?php if ($split_to_fields): ?>
    <?php echo $view['layout']->widget($block) ?>
<?php else: ?>
    <?php echo $view['form']->widget($form) ?>
<?php endif ?>

