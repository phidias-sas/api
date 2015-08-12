<?php
namespace Phidias\Api\Dispatcher;

class TemplateEngine
{
    private $variables = [];

    public function assign($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function render($templateFile)
    {
        foreach ($this->variables as $name => $value) {
            $$name = $this->variables[$name];
        }

        ob_start();
        include $templateFile;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}