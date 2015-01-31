<?php $attr['type'] = !empty($attr['type']) ? $view->escape($attr['type']) : 'text/css' ?>
<?php if (!empty($attr['src'])): ?>
    <?php $href = $attr['src']; unset($attr['src']); $attr['href'] = $href ?>
    <link rel="stylesheet" <?php echo $view['block']->block($block, 'block_attributes', array('attr' => $attr)) ?>/>
<?php else: ?>
    <style <?php echo $view['block']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
        <?php echo $content ?>
    </style>
<?php endif ?>
