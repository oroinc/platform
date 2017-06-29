<?php //@codingStandardsIgnoreFile?>
<?php if (!empty($type)) {
    $attr['type'] = $type;
} ?>
<?php if (!empty($async)) {
    $attr['async'] = 'async';
} ?>
<?php if (!empty($defer)) {
    $attr['defer'] = 'defer';
} ?>
<?php if (!empty($crossorigin)) {
    $attr['crossorigin'] = $crossorigin;
} ?>
<?php if (!empty($src)): ?>
    <?php $attr['src'] = $src; ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>></script>
<?php else: ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
        <?php echo $content ?>
    </script>
<?php endif ?>
