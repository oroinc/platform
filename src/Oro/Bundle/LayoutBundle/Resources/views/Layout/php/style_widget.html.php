<?php //@codingStandardsIgnoreFile?>
<?php if (!empty($type)) {
    $attr['type'] = $type;
} ?>
<?php if (!empty($media)) {
    $attr['media'] = $media;
} ?>
<?php if (!empty($scoped)) {
    $attr['scoped'] = 'scoped';
} ?>
<?php if (!empty($crossorigin)) {
    $attr['crossorigin'] = $crossorigin;
} ?>
<?php if (!empty($src)): ?>
    <?php $attr['href'] = $src; ?>
    <?php $attr = array_merge(['rel' => 'stylesheet'], $attr); ?>
    <link <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>/>
<?php else: ?>
    <style <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
        <?php echo $content ?>
    </style>
<?php endif ?>
