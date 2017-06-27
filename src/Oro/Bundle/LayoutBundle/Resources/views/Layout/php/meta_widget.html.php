<?php //@codingStandardsIgnoreFile?>
<?php if (!empty($charset)) {
    $attr['charset'] = $charset;
} ?>
<?php if (!empty($http_equiv)) {
    $attr['http_equiv'] = $http_equiv;
} ?>
<?php if (!empty($name)) {
    $attr['name'] = $name;
} ?>
<?php if (!empty($content)) {
    $attr['content'] = $content;
} ?>
<meta <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>/>
