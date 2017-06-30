<?php //@codingStandardsIgnoreFile?>
<?php foreach ($block as $child) : ?>
    <?php if ($child->vars['visible']): ?>
        <?php echo $view['layout']->widget($child) ?>
    <?php endif ?>
<?php endforeach; ?>
