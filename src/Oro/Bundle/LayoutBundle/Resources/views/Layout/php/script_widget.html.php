<?php $attr['type'] = !empty($attr['type']) ? $view->escape($attr['type']) : 'text/javascript' ?>
<?php if (!empty($attr['src'])): ?>
    <script <?php echo $view['block']->block($block, 'block_attributes', array('attr' => $attr)) ?>></script>
<?php else: ?>
    <script <?php echo $view['block']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
        <?php echo $content ?>
    </script>
<?php endif ?>
