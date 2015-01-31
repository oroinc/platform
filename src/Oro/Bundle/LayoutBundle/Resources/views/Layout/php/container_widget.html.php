<?php foreach ($block as $child) : ?>
    <?php echo $view['block']->widget($child) ?>
<?php endforeach; ?>
