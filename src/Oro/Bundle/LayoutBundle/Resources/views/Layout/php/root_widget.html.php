<?php //@codingStandardsIgnoreFile?>
<!DOCTYPE <?php echo !empty($doctype) ? $view->escape($doctype) : 'html' ?>>
<html>
    <?php echo $view['layout']->widget($block) ?>
</html>
