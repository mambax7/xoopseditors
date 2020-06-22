<?php
// Автор: andrey3761
// Копирайт: xoops.ws

xoops_load('XoopsEditor');

class FormRhEditor extends XoopsEditor
{

    // Инициализация заголовка
    private function initHeader()
    {
        static $init;

        if (!$init) {
            // Подключаем jQuery
            $GLOBALS['xoTheme']->addScript('browse.php?Frameworks/jquery/jquery.js');
            // Подключаем редактор
            $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/rheditor/editor/jquery.rheditor.js');
            $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/rheditor/editor/jquery.rheditor.xoopscode.js');
            $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/rheditor/editor/jquery.rheditor.css');

            $init = true;
        }

        return $init;
    }

    // Прорисовка
    public function render()
    {
        //
        $this->initHeader();

        $ret = '
<script type="text/javascript">
  $(document).ready(function() {
    $("#' . $this->getName() . '").rheditor()[0].focus();
  });
</script>
';
        $ret .= parent::render();

        return $ret;
    }
}


