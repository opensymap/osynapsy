<?php
namespace Osynapsy\Ocl\Component;

class Dummy extends Component
{
    public function __construct($name,$id=null)
    {
        parent::__construct('div',$name);
        $this->att('class','osy-dummy');
    }
    
    protected function __build_extra__()
    {
         if (!($txt = $this->getGlobal($this->id, $_REQUEST)))
         {
            $txt = $this->get_par('text');
            $txt = kkernel::ReplaceVariable($txt);
         }
         $this->add($txt);
    }
}
