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

use Osynapsy\Html\Tag;
use Osynapsy\Html\Bcl\Alert;

class Container extends Tag
{
    //use Trait for make form command buttons
    use FormCommands;
    
    private $alert;
    private $alertCount = 0;
    private $currentRow;
    private $foot;
    private $footLeft;
    private $footRight;
    
    public function __construct($id, $tag = 'div')
    {
        parent::__construct($tag, $id);
        if ($tag == 'form'){
            $this->att('method', 'post');
        }
    }

    public function alert($label, $type = 'danger')
    {
        if (empty($this->alert)) {
            $this->alert = $this->add(new Tag('div'));
            $this->alert->att('class','transition animated fadeIn m-b-sm');
        }
        $icon = '';
        switch ($type) {
            case 'danger':
                $icon = '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span><span class="sr-only">Error:</span>';
                break;
        }
        $alert = new Alert('al'.$this->alertCount, $icon.' '.$label, $type);
        $alert->att('class','alert-dismissible text-center',true)
              ->add(' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
        $this->alert->add($alert);
        $this->alertCount++;
        return $this->alert;
    }
    
    private function getFoot($right = false, $offset = 0)
    {
        if (empty($this->foot)) {
            $width = (12 - ($offset * 2)) / 2;
            $lgoffset = empty($offset) ? '' : ' col-lg-offset-'.$offset;
            $this->foot = $this->addRow();
            $this->footLeft = $this->foot->add(new Tag('div', null, 'col-lg-'.$width.$lgoffset));
            $this->footRight = $this->foot->add(new Tag('div', null, 'col-lg-'.$width.' text-right'));
        }
        return empty($right) ? $this->footLeft : $this->footRight;        
    }
    
    public function AddRow()
    {
        return $this->currentRow = $this->add(new Tag('div', null , 'row'));
    }
    
    public function AddColumn($lg = 4, $sm = null, $xs = null)
    {
        $col = new Column($lg);
        $col->setSm($sm);
        $col->setXs($xs);
        if (empty($this->currentRow)) {
            $this->AddRow();
        }
        return $this->currentRow->add($col);
    }
    
    public function setTitle($title)
    {
        $this->AddRow();
        $this->AddColumn(12)->add('<h1>'.$title.'</h1>');
    }
    
    public function setCommand($delete = false, $save = true, $back = true, $offset = 0)
    {
        if ($delete) {
            $this->getFoot(true, $offset)->add($this->getCommandDelete());                 
        }
        if ($save) {
            $this->getFoot(true, $offset)->add($this->getCommandSave($save));
        }        
        if ($back) {
            $this->getFoot()->add($this->getCommandBack());                 
        }
    }
}
