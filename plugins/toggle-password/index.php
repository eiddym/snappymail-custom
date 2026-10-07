<?php
class TogglePasswordPlugin extends \RainLoop\Plugins\AbstractPlugin
{
    public function Init() : void
    {
        $this->addJs('toggle.js');
    }
}
