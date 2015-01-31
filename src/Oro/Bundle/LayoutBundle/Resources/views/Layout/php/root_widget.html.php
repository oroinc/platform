<!DOCTYPE <?php echo !empty($doctype) ? $view->escape($doctype) : 'html' ?>>
<html>
    <?php echo $view['block']->widget($block) ?>
</html>
