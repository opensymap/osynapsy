<?php
namespace Osynapsy\Ocl\Component;

class Submit extends Button
{
    public function __construct($name,$id=null)
    {
        parent::__construct($name, $this->nvl($id, $name), 'submit');
    }
}
