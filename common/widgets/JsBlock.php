<?php
/**
 * js 块插件，js可以直接写在里面，会自动渲染到页面底端
 */

namespace common\widgets;


use yii\base\Widget;
use yii\web\View;

class JsBlock extends Widget
{
    public $key;
    public $position = View::POS_READY;
    public $viewFile;
    public $viewParams = [];
    /**
     * Starts recording a block.
     */
    public function init()
    {
        if ($this->viewFile === null) {
            ob_start();
            ob_implicit_flush(false);
        }
    }
    /**
     * Ends recording a block.
     * This method stops output buffering and saves the rendering result as a named block in the view.
     */
    public function run()
    {
        if ($this->viewFile === null) {
            $block = ob_get_clean();
        } else {
            $block = $this->view->render($this->viewFile, $this->viewParams);
        }
        $block = trim($block);
        $jsBlockPattern = '|^<script[^>]*>(?P<block_content>.+?)</script>$|is';
        if (preg_match($jsBlockPattern, $block, $matches)) {
            $block = $matches['block_content'];
        }
        $this->view->registerJs($block, $this->position, $this->key);
    }
}