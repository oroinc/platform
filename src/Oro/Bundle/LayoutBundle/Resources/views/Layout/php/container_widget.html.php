<?php foreach ($block as $child) : ?>
    <?php echo $view['layout']->widget($child) ?>
<?php endforeach; ?>
