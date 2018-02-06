<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Bcl;

use Osynapsy\Html\Component;
use Osynapsy\Html\Ocl\HiddenBox;

class LabelBox extends Component
{
    protected $hiddenBox;
    protected $label;
    
    public function __construct($id, $label='')
    {
        $this->requireCss('Bcl/LabelBox/style.css');
        parent::__construct('div', $id.'_labelbox');
        $this->att('class','osynapsy-labelbox');
        $this->hiddenBox = $this->add(new HiddenBox($id));
        $this->add($label);
    }
    
    public function setValue($value)
    {
        $this->hiddenBox->att('value',$value);
    }
    
    public function setLabelFromSQL($db, $sql, $par=array())
    {
        $this->label = $db->execUnique($sql, $par);
    }
    
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    public function __build_extra__()
    {
        if (is_null($this->label)) {
            $this->add(isset($_REQUEST[$this->hiddenBox->id]) ? $_REQUEST[$this->hiddenBox->id] : null);
        } else {
            $this->add('<span>'.$this->label.'</span>');
        }
    }
}
