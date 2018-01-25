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
use Osynapsy\Html\Tag;

/**
 * Description of CheckBox
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class CheckBox extends Component
{
    private $checkbox;
    private $hidden;
    
    public function __construct($id, $label, $value='1')
    {
        parent::__construct('label', $id.'_parent');
        $this->att('class','form-check-label');        
        $this->hidden = $this->add(new HiddenBox($id))->att('value', '');
        $this->checkbox = $this->add(new Tag('input'))->att([
            'id' => $id,
            'type' => 'checkbox',
            'name' => $id,
            'value' => $value
        ]);
        $this->add(' '.$label);
    }
    
    public function getCheckBox()
    {
        return $this->checkbox;
    }
}

