<?php
/*
 +-----------------------------------------------------------------------+
 | lib/components/odatagrid.php                                          |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create page form for generate datagrid and treegrid                 |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   28/08/2013
 * @date-update     28/08/2013
 */
namespace Osynapsy\Html\Ocl;

use Osynapsy\Html\Tag as Tag;
use Osynapsy\Html\Component as Component;
use Osynapsy\Html\Ocl\HiddenBox as HiddenBox;

class DataGrid extends Component
{
    private $__col = array();
    private $__dat = array();
    private $__grp = array(); //array contenente i dati raggruppati
    private $__sta = array();
    private $db  = null;
    private $toolbar;
    private $columns = array();
    private $columnProperties = array();

    public function __construct($name)
    {
        $this->requireJs('/__assets/osynapsy/Ocl/DataGrid/script.js');
        $this->requireCss('/__assets/osynapsy/Ocl/DataGrid/style.css');
        parent::__construct('div',$name);       
        $this->att('class','osy-datagrid-2');
        $this->__par['type'] = 'datagrid';
        $this->__par['row-num'] = 10;        
        $this->__par['max_wdt_per'] = 96;
        $this->__par['column-object'] = array();
        $this->__sta['col_len'] = array();
        $this->__par['paging'] = true;
        $this->__par['error-in-sql'] = false;
        $this->__par['record-add'] = null;
        $this->__par['record-add-label'] = '<span class="glyphicon glyphicon-plus"></span>';
        $this->__par['datasource-sql-par'] = array();        
        $this->__par['head-hide'] = 0;
        $this->__par['border'] = 'on';
    }

    public function toolbarAppend($cnt, $label='&nbsp;')
    {
        $this->getToolbar()->add(
            '<div class="form-group">'.
            '<label>'.$label.'</label>'.
            $cnt.
            '</div>'
        );
        return $this->toolbar;
    }

    protected function __build_extra__() {

        //$this->loadColumnObject();
        if ($this->rows) {
            $this->__par['row-num'] = $this->rows;
        }
        if ($this->get_par('datasource-sql')) {
            $this->dataLoad();
        }
        if ($par = $this->get_par('mapgrid-parent')) {
            $this->att('data-mapgrid', $par);
        }
        if ($this->get_par('mapgrid-parent-refresh')) {
            $this->att('class','mapgrid-refreshable',true);
        }
        if ($par = $this->get_par('mapgrid-infowindow-format')) {
            $this->att('data-mapgrid-infowindow-format', $par);
        }
        //Aggiungo il campo che conterrà i rami aperti dell'albero.
        $this->add(new HiddenBox($this->id.'_open'));
        $this->add(new HiddenBox($this->id.'_order'));
        //Aggiungo il campo che conterrà il ramo selezionato.
        $this->add(new HiddenBox($this->id,$this->id.'_sel'));
        $tbl_cnt = $this->add(tag::create('div'))
                        ->att('id',$this->id.'-body')
                        ->att('class','osy-datagrid-2-body table-responsive')
                        ->att('data-rows-num',$this->__par['rec_num']);
        $this->buildAddButton($tbl_cnt);
        $hgt = $this->get_par('cell-height');

        if (!empty($this->__par['row-num']) && !empty($hgt))
        {
            $hgt = str_replace('px','', $hgt);
            $tbl_cnt->att('style','height : '. ($hgt-30) . 'px',true);
        } elseif(!empty($hgt)) {
            $hgt = str_replace('px','', $hgt);
            $tbl_cnt->att('style','height : '. $hgt . 'px',true);
        }

        $tbl = $tbl_cnt->add(tag::create('table'));

        $tbl->att('data-toggle','table')
            ->att('data-show-columns',"false")
            ->att('data-search','false')
            ->att('data-toolbar','#'.$this->id.'_toolbar')
            ->att('class','display table table-bordered dataTable no-footer border-'.$this->__par['border']);
        if ($err = $this->get_par('error-in-sql')) {
            $tbl->add(tag::create('tr'))->add(tag::create('td'))->add($err);
            return;
        }
        if (is_array($this->get_par('cols'))) {
            $tbl_hd = $tbl->add(tag::create('thead'));
            $this->buildHead($tbl_hd);
        }
        if (is_array($this->__dat) && !empty($this->__dat)) {
            $tbl_bod = $tbl->add(tag::create('tbody'));
            $lev = ($this->get_par('type') == 'datagrid') ? null : 0;
            $this->buildBody($tbl_bod,$this->__dat,$lev);
        } else {
            $tbl->add(tag::create('td'))->att('class','no-data text-center')->att('colspan',$this->__par['cols_vis'])->add('Nessun dato presente');
        }
        $t = array_sum($this->__sta['col_len']);
        foreach ($this->__sta['col_len'] as $k => $l) {
            $p = ($this->__par['max_wdt_per'] * $l) / max($t,1);
        }
        //Setto il tipo di componente come classe css in modo da poterlo testare via js.
        $this->att('class',$this->get_par('type'),true);

        $this->buildPaging();
    }

    public function getToolbar()
    {
        if (!empty($this->toolbar)) {
            return $this->toolbar;
        }
        $this->toolbar = $this->add(new Tag('div'))->att([
            'id' => $this->id.'_toolbar',
            'class' => 'osy-datagrid-2-toolbar row'
        ]);  
        return $this->toolbar;
    }

    private function buildAddButton($cnt)
    {
        if ($view = $this->__par['record-add']){
            $this->getToolbar()
                 ->add(new Tag('button'))
                 ->att('id',$this->id.'_add')
                 ->att('type','button')
                 ->att('class','btn btn-primary cmd-add pull-right')
                 ->att('data-view',$view)
                 ->add($this->__par['record-add-label']);
        }
    }
    
    private function buildBody($container, $data, $lev, $ico_arr = null)
    {
        if (!is_array($data)) {
            return;
        }
        $i = 0;
        $l = count($data);
        $ico_tre = null;

        foreach ($data as $row) {
            if (!is_null($lev)) {
                if (($i+1) == $l) {
                    $ico_tre = 3;
                    $ico_arr[$lev] = null;
                } elseif(empty($i)) {
                    $ico_tre = empty($lev) ? 1 : 2;
                    $ico_arr[$lev] = (($i+1) != $l) ? '4' : null;
                } else {
                    $ico_tre = 2;
                    $ico_arr[$lev] = (($i+1) != $l) ? '4' : null;
                }
            }
            $this->buildRow($container,$row,$lev,$ico_tre,$ico_arr);
            if ($this->get_par('type') == 'treegrid') {
                @list($item_id,$group_id) = explode(',',$row['_tree']);
                $this->buildBody($container,@$this->__grp[$item_id],$lev+1,$ico_arr);
            }
            $i++;
        }
    }
    
    protected function formatOption($opt)
    {
        return $opt;
    }
    
    private function buildHead($thead)
    {
        $tr = new Tag('tr');
        $cols = $this->get_par('cols');
        foreach ($cols as $k => $col) {            
            $opt = array(
                'alignment'=> '',
                'class'    => $this->getColumnProperty($k, 'class'),
                'color'    => '',
                'format'   => '',
                'hidden'   => false,
                'print'    => true,
                'realname' => strip_tags($col['name']),
                'style'    => $this->getColumnProperty($k, 'style'),
                'title'    => $col['name']
            );
            
            switch ($opt['title'][0]) {
                case '_':
                    $opt['print'] = false;
                    @list($cmd, $nam, $par) = explode(',',$opt['title']);
                    switch ($cmd) {
                        case '_tree':
                            $this->att('class','osy-treegrid',true);
                            $this->dataGroup();
                            break;
                        case '_chk'   :
                        case '_chk2'  :
                            if ($nam == 'sel'){
                                $opt['title'] = '<span class="fa fa-check-square-o osy-datagrid-cmd-checkall"></span>';
                                $opt['class'] = 'no-ord';
                            } else {
                                $opt['title'] = $nam;
                            }
                            $opt['print'] = true;
                            break;
                        case '_rad'   :
                            $opt['title'] = '&nbsp;';
                            $opt['print'] = true;
                            break;                        
                        case '_!html' :
                            $opt['class'] .= ' text-center';
                        case '_button':
                        case '_html'  :
                        case '_text'  :
                        case '_img'   :
                        case '_img64' :
                        case '_img64x2':
                        case '_center':
                            $opt['title'] = $nam;
                            $opt['print'] = true;
                            break;
                        case '_pk'  :
                        case '_rowid':
                            $this->par('rowid',$k);
                            break;
                    }
                    break;

               case '!':
                    $opt['class'] .= ' text-center';
               case '$':
                    $opt['title'] = str_replace(array('$','?','#','!'),array('','','',''),$opt['title']);
                    break;
            }
            
            $opt = $this->formatOption($opt);
            
            if ($opt['print']) {
                $this->__par['cols_vis'] += 1;
                $cel = $tr->add(new Tag('th'))
                          ->att('real_name',$opt['realname'])
                          ->att('data-ord',$k+1);
                if ($opt['class']) {
                     $cel->att('class',trim($opt['class']),true);
                }

                $cel->att('data-type',$col['native_type'])
                    ->add('<span>'.$opt['title'].'</span>');
                if (!empty($_REQUEST[$this->id.'_order'])) {
                    if (strpos($_REQUEST[$this->id.'_order'],'['.($k+1).']') !== false) {
                        $cel->att('class','osy-datagrid-asc');
                        $cel->add(' <span class="orderIcon glyphicon glyphicon-sort-by-alphabet"></span>');
                    } elseif (strpos($_REQUEST[$this->id.'_order'],'['.($k+1).' DESC]') !== false) {
                        $cel->att('class','osy-datagrid-desc');
                        $cel->add(' <span class="orderIcon glyphicon glyphicon-sort-by-alphabet-alt"></span>');
                    }
                }
            }
        }
        if (!$this->get_par('head-hide')){
            $thead->add($tr);
        }
    }

    private function buildRow(&$grd, $row, $lev = null, $pos = null, $ico_arr = null)
    {
        $t = $i = 0;
        $orw = tag::create('tr');
        $orw->tagdep = (abs($grd->tagdep) + 1) * -1;
        $opt = array(
            'row' => array(
                'class'  => array(),
                'prefix' => array(),
                'style'  => array(),
                'attr'   => array(),
                'cell-style-inc',array()
            ),
            'cell' => array()
        );

        foreach ($row as $k => $v) {
            if (array_key_exists($k, $this->columns)) {
                $k = empty($this->columns['raw']) ? $k : $this->columns['raw'];
            }
            
            $cel = new Tag('td');

            $opt['cell'] = array(
                'alignment'=> '',
                'class'    => array($this->getColumnProperty($i, 'class')),
                'color'    => '',
                'command'  => '',
                'format'   => '',
                'function' => $this->getColumnProperty($t, 'function'),
                'hidden'   => false,
                'parameter'=> '',
                'print'    => true,
                'rawtitle' => $k,
                'rawvalue' => $v,
                'style'    => array($this->getColumnProperty($i, 'style')),
                'title'    => $k,
                'value'    => htmlentities($v)                
            );

            switch ($opt['cell']['rawtitle'][0]) {
                case '_':
                    @list($opt['cell']['format'], $opt['cell']['title'], $opt['cell']['parameter']) = explode(',',$opt['cell']['rawtitle']);
                    break;
                case '$':
                case '€':
                    $opt['cell']['format'] = 'money';
                    break;
                case '!':
                    $opt['cell']['class'][] = 'center';
                    break;
            }
            
            $opt['cell'] = $this->formatOption($opt['cell']);
            
            if (!empty($opt['cell']['format'])){
                list($opt, $lev, $pos, $ico_arr) = $this->formatCellValue($opt, $lev, $pos, $ico_arr, $row);
                //var_dump($opt['row']);
            }
            if (!empty($opt['cell']['function'])) {
                $opt['cell']['value'] = $opt['cell']['function']($opt['cell']['value'], $row);
            }
            $t++; //Incremento l'indice generale della colonna
            if (!empty($opt['row']['cell-style-inc'])){
                $cel->att('style',implode(' ',$opt['row']['cell-style-inc']));
            }
            if (!empty($opt['row']['style'])){
                $orw->att('style',implode(' ',$opt['row']['style']));
            }
            //Non stampo la colonna se in $opt['cell']['print'] è contenuto false
            if (!$opt['cell']['print']) {
                continue;
            }
            if (!empty($opt['cell']['class'])){
                $cel->att('class',trim(implode(' ',$opt['cell']['class'])));
            }
            //Formatto tipi di dati particolari
            if (!empty($opt['row']['prefix'])){
                $cel->addFromArray($opt['row']['prefix']);
                $opt['row']['prefix'] = array();
            }
            if (!empty($this->__col[$i]) && is_array($this->__col[$i])){
                $this->__build_attr($cel,$this->__col[$i]);
            }
            if (array_key_exists($i,$this->__sta['col_len'])){
                $this->__sta['col_len'][$i] = max(strlen($opt['cell']['rawvalue']),$this->__sta['col_len'][$i]);
            } else {
                $this->__sta['col_len'][$i] = strlen($opt['cell']['rawvalue']);
            }
            $cel->add(($opt['cell']['value'] !== '0' && empty($opt['cell']['value'])) ? '&nbsp;' : nl2br($opt['cell']['value']));
            $orw->add($cel);
            $i++;//Incremento l'indice delle colonne visibili
        }
        if (!empty($opt['row']['class'])){
            $orw->att('class',implode(' ',$opt['row']['class']));
        }
        if (!empty($opt['row']['attr'])){
            foreach ($opt['row']['attr'] as $item){
              $orw->att($item[0],$item[1]);
            }
        }        
        $grd->add($orw.'');
    }
    
    protected function formatCellOption($opt, $lev, $pos, $ico_arr, $data)
    {
        return $opt;
    }
    
    private function formatCellValue($opt, $lev, $pos, $ico_arr = null, $data = array())
    {
        $opt['cell']['print'] = false;
        
        switch ($opt['cell']['format'])
        {
            case '_attr':
            case 'attribute':
                $opt['row']['attr'][] = array($opt['cell']['title'],$opt['cell']['value']);
                break;
            case '_bgcolor':
                if (!empty($opt['cell']['value'])) {
                    $opt['row']['style'][] = 'background: '.$opt['cell']['value'];
                }
                break;
            case 'color':
            case '_color':
            case '_color2':
            case '_color3':
                $opt['row']['cell-style-inc'][] = 'color: '.$opt['cell']['value'].';';
                break;
            case 'date':
                $dat = date_create($opt['cell']['rawvalue']);
                $opt['cell']['value'] = date_format($dat, 'd/m/Y H:i:s');
                $opt['cell']['class'][] = 'center';
                $opt['cell']['print'] = true;
                break;
            case '_button':
                list($v,$par) = explode('[,]',$opt['cell']['rawvalue']);
                if (!empty($v)){
                    $opt['cell']['value'] = "<input type=\"button\" name=\"btn_row\" class=\"btn_{$this->id}\" value=\"$v\" par=\"{$par}\">";
                    $opt['cell']['class'][] = 'center';
                } else {
                    $opt['cell']['value'] = '&nbsp;';
                }
                $opt['cell']['print'] = true;
                break;
            case '_chk':
                list($v,$sel) = explode('#',$opt['cell']['rawvalue']);                
                if ($v === '0' || !empty($v)) {
                    $opt['cell']['value'] = "<input type=\"checkbox\" name=\"chk_{$this->id}[]\" value=\"{$v}\"".(empty($sel) ? '' : ' checked').">";
                }
                $opt['cell']['class'][] = 'center';
                $opt['cell']['print'] = true;
                break;
            case '_rad':
                if (!empty($opt['cell']['rawvalue'])){
                    $opt['cell']['value'] = "<input type=\"radio\" class=\"rad_{$this->id}\" name=\"rad_{$this->id}\" value=\"{$opt['cell']['rawvalue']}\"".($opt['cell']['rawvalue'] == $_REQUEST['rad_'.$this->id] ? ' checked="checked"' : '').">";
                    $opt['cell']['class'][] = 'center';
                }
                $opt['cell']['print'] = true;
                break;
            case '_tree':
                //Il primo elemento deve essere l'id dell'item il secondo l'id del gruppo di appartenenza
                @list($tree_id, $tree_group) = explode(',',$opt['cell']['rawvalue']);
               // var_dump($v);
                $opt['row']['attr'][] = array('oid',base64_encode($tree_id));
                $opt['row']['attr'][] = array('gid',base64_encode($tree_group));
                $opt['row']['attr'][] = array('data-treedeep',$lev);
                if (array_key_exists($this->id,$_REQUEST) && $_REQUEST[$this->id] == '['.$tree_id.']'){
                    $opt['row']['class'][] = 'sel';
                }
                if (is_null($lev)) {
                    break;
                }
                $ico = '';
                for($ii = 0; $ii < $lev; $ii++) {
                    $cls  = empty($ico_arr[$ii]) ? 'tree-null' : ' tree-con-'.$ico_arr[$ii];
                    $ico .= '<span class="tree '.$cls.'">&nbsp;</span>';
                }
                $ico .= array_key_exists($tree_id,$this->__grp)
                       ? '<span class="tree tree-plus-'.$pos.'">&nbsp;</span>'
                       : '<span class="tree tree-con-'.$pos.'">&nbsp;</span>';
                $opt['row']['prefix'][] = $ico;
                if (!empty($lev) && strpos($_REQUEST[$this->id.'_open'], '['.base64_encode($tree_group).']') === false){
                    $opt['row']['class'][] = 'hide';
                }
                break;           
            case '_!html':
                $opt['cell']['class'][] = 'text-center';
            case '_html' :
            case 'html'  :
                $opt['cell']['print'] = true;
                $opt['cell']['value'] = $opt['cell']['rawvalue'];
                break;
            case '_ico'  :
                $opt['row']['prefix'][] = "<img src=\"{$opt['cell']['rawvalue']}\" class=\"osy-treegrid-ico\">";
                break;
            case '_faico'  :
                $opt['row']['prefix'][] = "<span class=\"fa {$opt['cell']['rawvalue']}\"></span>&nbsp;";
                break;
           case '_pk'   :
                $opt['row']['attr'][] = array('_pk',$opt['cell']['rawvalue']);
                break;
            case '_img':
                $opt['cell']['print'] = true;
                $opt['cell']['value'] = empty($opt['cell']['rawvalue']) ? '<span class="fa fa-ban"></span>': '<img src="'.$opt['cell']['rawvalue'].'" style="width: 64px;">';
                $opt['cell']['class'][] = 'text-center';
                break;
            case '_img64x2':
                $dimcls = 'osy-image-med';
                //No break
            case '_img64':
                $opt['cell']['print'] = true;
                $opt['cell']['class'][] = 'text-center';
                $opt['cell']['value'] = '<span class="'.(empty($dimcls) ? 'osy-image-min' : $dimcls).'">'.(empty($opt['cell']['rawvalue']) ? '<span class="fa fa-ban"></span>': '<img src="data:image/png;base64,'.base64_encode($opt['cell']['rawvalue']).'">').'</span>';
                break;
            case 'money':
                $opt['cell']['print'] = true;
                if (is_numeric($opt['cell']['rawvalue'])) {
                    $opt['cell']['value'] = number_format($opt['cell']['rawvalue'],2,',','.');
                }
                $opt['cell']['class'][] = 'text-right';
                break;
            case 'center':
            case '_center':
                $opt['cell']['class'][] = 'text-center';
                $opt['cell']['print'] = true;
                break;
        }
        
        return array(
            $this->formatCellOption($opt, $lev, $pos, $ico_arr, $data),
            $lev,
            $pos,
            $ico_arr
        );
    }

    private function buildPaging()
    {
        if (empty($this->__par['row-num'])) {
            return '';
        }
        if (empty($this->__par['pag_tot'])) {
            return;
        }
        $foot = '<div class="osy-datagrid-2-foot text-center">';
        $foot .= '<button type="button" name="btn_pag" data-mov="start" value="&lt;&lt;" class="btn btn-primary btn-xs osy-datagrid-2-paging">&lt;&lt;</button>';
        $foot .= '<button type="button" name="btn_pag" data-mov="-1" value="&lt;" class="btn btn-primary btn-xs  osy-datagrid-2-paging">&lt;</button>';
        $foot .= '<span>&nbsp;<input type="hidden" name="'.$this->id.'_pag" id="'.$this->id.'_pag" value="'.$this->__par['pag_cur'].'" class="osy-datagrid-2-pagval history-param" data-pagtot="'.$this->__par['pag_tot'].'"> Pagina '.$this->__par['pag_cur'].' di <span id="_pag_tot">'.$this->__par['pag_tot'].'</span>&nbsp;</span>';
        $foot .= '<button type="button" name="btn_pag" data-mov="+1" value="&gt;" class="btn btn-primary btn-xs  osy-datagrid-2-paging">&gt;</button>';
        $foot .= '<button type="button" name="btn_pag" data-mov="end" value="&gt;&gt;" class="btn btn-primary btn-xs  osy-datagrid-2-paging">&gt;&gt;</button>';
        $foot .= '</div>';
        $this->add($foot);
    }

    private function dataLoad()
    {
        $sql = $this->get_par('datasource-sql');
        if (empty($sql)) {
            return;
        }
        try {
            $sql_cnt = "SELECT COUNT(*) FROM (\n{$sql}\n) a ".$whr;
            $this->__par['rec_num'] = $this->db->execUnique($sql_cnt,$this->get_par('datasource-sql-par'));
            $this->att('data-row-num',$this->__par['rec_num']);
        } catch(\Exception $e) {
            $this->par('error-in-sql','<pre>'.$sql_cnt."\n".$e->getMessage().'</pre>');
            return;
        }

        if ($this->__par['row-num'] > 0) {
            $this->__par['pag_tot'] = ceil($this->__par['rec_num'] / $this->__par['row-num']);
            $this->__par['pag_cur'] = !empty($_REQUEST[$this->id.'_pag']) ? min($_REQUEST[$this->id.'_pag']+0,$this->__par['pag_tot']) : 1;

            if (!empty($_REQUEST['btn_pag']))
            {
                switch ($_REQUEST['btn_pag']) {
                    case '<<':
                        $this->__par['pag_cur'] = 1;
                        break;
                    case '<':
                        if ($this->__par['pag_cur'] > 1){
                            $this->__par['pag_cur']--;
                        }
                        break;
                    case '>':
                        if ($this->__par['pag_cur'] < $this->__par['pag_tot']){
                            $this->__par['pag_cur']++;
                        }
                        break;
                    case '>>' :
                        $this->__par['pag_cur'] = $this->__par['pag_tot'];
                        break;
                }
            }
        }

        switch ($this->db->getType()) {
            case 'oracle':
                $sql = "SELECT a.*
                        FROM (
                                 SELECT b.*,rownum as \"_rnum\"
                                  FROM (
                                         SELECT a.*
                                         FROM ($sql) a
                                         ".(empty($whr) ? '' : $whr)."
                                         ".(!empty($_REQUEST[$this->id.'_order']) ? ' ORDER BY '.str_replace(array('][','[',']'),array(',','',''),$_REQUEST[$this->id.'_order']) : '')."
                                        ) b
                            ) a ";
                if (!empty($this->__par['row-num']) && array_key_exists('pag_cur', $this->__par)) {
                    $row_sta = (($this->__par['pag_cur'] - 1) * $this->__par['row-num']) + 1 ;
                    $row_end = ($this->__par['pag_cur'] * $this->__par['row-num']);
                    $sql .=  "WHERE \"_rnum\" BETWEEN $row_sta AND $row_end";
                }
                break;
            default:
                $sql = "SELECT a.* FROM ({$sql}) a {$whr} ";
                if (!empty($_REQUEST[$this->id.'_order'])) {
                    $sql .= ' ORDER BY '.str_replace(array('][','[',']'),array(',','',''),$_REQUEST[$this->id.'_order']);
                }

                if (!empty($this->__par['row-num']) && array_key_exists('pag_cur',$this->__par)) {
                    $row_sta = (($this->__par['pag_cur'] - 1) * $this->__par['row-num']);
                    $row_sta =  $row_sta < 0 ? 0 : $row_sta;
                    $sql .= ($this->db->getType() == 'pgsql')
                           ? "\nLIMIT ".$this->get_par('row-num')." OFFSET ".$row_sta
                           : "\nLIMIT $row_sta , ".$this->get_par('row-num');
                }
                break;
        }
        //Eseguo la query        
        try {
            $this->__dat = $this->db->execQuery($sql,$this->get_par('datasource-sql-par'),'ASSOC');
        } catch (\Exception $e) {
            die($sql.$e->getMessage());
        }
        //Salvo le colonne in un option
        $this->__par['cols'] = $this->db->getColumns();
        $this->__par['cols_tot'] = count($this->__par['cols']);
        $this->__par['cols_vis'] = 0;
        if (is_array($this->__par['cols'])) {
            $this->__par['cols_tot'] = count($this->__par['cols']);
        }        
    }

    private function dataGroup()
    {
        $this->par('type','treegrid');
        $dat = [];
        foreach ($this->__dat as $k => $v) {
            @list($oid, $gid) = explode(',',$v['_tree']);
            if (!empty($gid)) {
                $this->__grp[$gid][] = $v;
            } else {
                $dat[] = $v;
            }
        }       
        $this->__dat = $dat;
    }

    public function getColumns()
    {
        return $this->__col;
    }

    public function setDatasource($array)
    {
        $this->__dat = $array;
    }

    public function setColumn($id, $name=null, $idx=null)
    {
        $name = empty($name) ? $id : $name;
        $idx = is_null($idx) ? count($this->__par['cols']) : $idx;
        $this->__par['cols'][$idx] = array('name' => $name);
        $this->columns[$id] = array('name' => $name);
    }

    public function setColumnProperty($n, $prop)
    {
        if (is_array($prop)) {
            $this->columnProperties[$n] = $prop;
        }
    }

    public function getColumnProperty($n, $propertyKey)
    {
        if (empty($this->columnProperties[$n])) {
            return '';
        }
        if (empty($this->columnProperties[$n][$propertyKey])) {
            return '';
        }
        return $this->columnProperties[$n][$propertyKey];
    }

    public function SetSql($db, $sql, $par=array())
    {
        $this->db = $db;
        $this->__par['datasource-sql'] = $sql;
        $this->__par['datasource-sql-par'] = $par;
    }
}