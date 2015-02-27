<ul <?php echo $view['layout']->block($block, 'block_attributes') ?>>
<?php foreach ($block as $child) : ?>
    <?php if ($child->vars['visible']): ?>
        <li><?php echo $view['layout']->widget($child) ?></li>
    <?php endif ?>
<?php endforeach; ?>
</ul>
