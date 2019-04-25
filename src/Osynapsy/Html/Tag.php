<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html;

class Tag
{
    private $att = [];
    private $cnt = [];
    
    public $ref = array(); 
    public $tagdep = 0;
    public $parent = null;
    
    public function __construct($tag = 'dummy', $id = null, $class = null)
    {
        $this->att(0,$tag);
        if (!empty($id)) {
            $this->att('id', $id);
        }
        if (!empty($class)) {
            $this->att('class', $class);
        }
    }
    
    public function __get($attribute)
    {
        if ($attribute == 'tag') {
            return $this->att[0];
        }
        return array_key_exists($attribute, $this->att) ? $this->att[$attribute] : null;
    }
    
    public function __set($attribute, $value)
    {
       $this->att[$attribute] = $value;
    }
    
    public function add($a, $d='last')
    {
        if (is_object($a)) {
            if ($a instanceof tag) {
                $a->tagdep = abs($this->tagdep) + 1;
                $this->tagdep = abs($this->tagdep) * -1;
            }
            if ($a->id && array_key_exists($a->id,$this->ref)) {
                $a = $this->ref[$a->id];
                return $a;
            } elseif ($a->id) {
                $this->ref[$a->id] = $a;
            }
            $a->parent =& $this;
        }
        if ($d == 'last') {
            if (is_array($this->cnt)) {
                array_push($this->cnt,$a);
            } 
        } else {
            array_unshift($this->cnt,$a);
            ksort($this->cnt);
        }
        return $a;
    }
    
    public function addFromArray($a)
    {
        if (!is_array($a)) {
            return $this->add($a);
        }
        foreach ($a as $t) {
            $this->add($t);
        }
        return $t;
    }
    
    public function att($attribute, $value = '', $concat = false)
    {
        if (is_array($attribute)) {
            foreach ($attribute as $key => $value) {
                $this->att[$key] = $value;
            }            
        } elseif ($concat && !empty($this->att[$attribute])) {            
            $this->att[$attribute] .= ($concat === true ? ' ' : $concat) . $value;
        } else {
            $this->att[$attribute] = $value;
        }
        return $this;
    }
    
    protected function build()
    {
        $strContent = '';
        foreach ($this->cnt as $content) {
            $strContent .= $content;
        }
        $tag = array_shift($this->att);
        if ($tag == 'dummy') {
            return $strContent;
        }
        $spaces = $strTag = '';
        if (!empty($tag)){
            $spaces = $this->tagdep != 0 ? "\n".str_repeat("  ",abs($this->tagdep)) : '';
            $strTag = $spaces.'<'.$tag;
            foreach ($this->att as $key => $val) {
                $strTag .= ' '.$key.'="'.htmlspecialchars($val, ENT_QUOTES).'"';
                // la conversione del contentuto degli attributi viene fornita da Tag in modo
                // tale che non debba essere gestito dai suoi figli
                /*$strTag .= ' '.$key.'="'.$val.'"';*/
            }
            $strTag .= '>';
        }
        if (!in_array($tag, array('input', 'img', 'link', 'meta'))) {
            $spaces2 = $this->tagdep < 0 ? $spaces : '';
            $strTag .= $strContent . (!empty($tag) ? $spaces2."</{$tag}>" : '');
        }
        return $strTag;
    }
    
    public static function create($tag,$id=null)
    {
        return new tag($tag,$id);
    }
    
    public function get()
    {
        return $this->build();
    }
    
    public function child($index = 0)
    {
        if (is_null($index)) {
            return $this->cnt;   
        }
        if (array_key_exists($index, $this->cnt)) {
            return $this->cnt[$index];
        }
        return false;
    }
    
    public function isEmpty()
    {
        return count($this->cnt) > 0 ? false : true;
    }

    public function __toString()
    {
        try {
            return $this->get();
        } catch (\Exception $e) {
            //var_dump($str);
            trigger_error($e->getMessage());
            echo '<pre>';
            var_dump(debug_backtrace(10));
            echo '</pre>';
            return $this->id;
        }
    }
    
    public function set($content)
    {
        $this->cnt = array($content);
    }
}
