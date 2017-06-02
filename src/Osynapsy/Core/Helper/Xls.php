<?php
namespace Osynapsy\Core\Helper;

use PHPExcel;

class Xls 
{
    private $db;
    private $error = array();
    private $delimiter = null;
    private $lineending = null;
    
    public function __construct()
    {
    }
    
    public function loadExcel($fileName,$grabNumRow=null)
    {
        try {
            $fileType = \PHPExcel_IOFactory::identify($fileName);           
            $reader = \PHPExcel_IOFactory::createReader($fileType);
            switch($fileType) {
                case 'CSV':
                    if (!is_null($this->delimiter)) {
                        $reader->setDelimiter($this->delimiter);
                    }
                    if (!is_null($this->lineending)) {
                        //$reader->setLineEnding($this->lineending);
                    }
                    break;
            }            
            $excel = $reader->load($fileName);
            //  Get worksheet dimensions
            $sheet = $excel->getSheet(0); 
            $maxRow = $sheet->getHighestRow(); 
            $maxCol = $sheet->getHighestDataColumn();
            $data = array();
            for ($row = 1; $row <= $maxRow; $row++) {
                $data[] = $sheet->rangeToArray('A' . $row . ':' . $maxCol . $row, NULL, TRUE, FALSE);
                if (!empty($grabNumRow) && $row <= $grabNumRow){
                    break;
                }
            }
            return $data;
        } catch (\Exception $e) {
            return $maxCol.'Errore nell\'apertura del file "'.pathinfo($fileName,PATHINFO_BASENAME).'": '.$e->getMessage();
        }
    }
    
    public function isValidFile($fileName) 
    {
        try {
            $fileType = \PHPExcel_IOFactory::identify($fileName);
            $reader = \PHPExcel_IOFactory::createReader($fileType);
            $excel = $reader->load($fileName);
            return $excel;
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function import($db, $table, $fields, $data, $constant=array())
    {
        $this->db = $db;
        if (empty($table)) {
            $this->error[] = 'Table is empty';
        }
        if (empty($fields)) {
            $this->error[] = 'Fields is empty';
        }
        if (!empty($this->error)) {
            return false;
        }
        //  Loop through each row of the worksheet in turn
        $insert = 0;
        foreach ($data as $k => $rec) { 
            if (empty($rec)) {
                continue;
            }
            $sqlParams = array();
            foreach ($fields as $column => $field) {
                if (empty($field)) {
                    continue;
                }
                $sqlParams[$field] = !empty($rec[0][$column]) ? $rec[0][$column] : null ;
            }
            
            foreach($constant as $field => $value) {
                $sqlParams[$field] = $value;
            }
            
            if (!empty($sqlParams)){
                try {
                    $this->db->insert($table, $sqlParams);
                    $insert++;
                } catch (\Exception $e) {
                    $this->error[] = "Row n. $k not imported";
                }
            }
        }
        
        return $insert;
    }
    
    public function export($data, $title='Data export')
    {
        $xls = new \PHPExcel();
        
        $xls->getProperties()->setCreator("Whiterabbit suite");
        $xls->getProperties()->setLastModifiedBy("Whiterabbit suite");
        $xls->getProperties()->setTitle($title);
        $xls->getProperties()->setSubject("Data Export");
        $xls->getProperties()->setDescription("Data export from Whiterabbit suite");
        
        $letters = array_unshift(range('A','Z'),'');
        $cell = '';
        
        function getColumnId($n) {
            $l = range('A','Z');
            if ($n <= 26) return $l[$n-1];
            $r = ($n % 26);
            $i = (($n - $r) / 26) - (empty($r) ? 1 : 0);
            return getColumnId($i).(!empty($r) ? getColumnId($r) : 'Z');
        }
        
        for ($i = 0; $i < count($data); $i++) {
            $j = 0;
            foreach ($data[$i] as $k => $v) {
                if ($k[0] == '_') continue;
                $col = getColumnId($j+1);
                $cel = $col.($i+2);
                try{
                    if (empty($i)) {
                        $xls->getActiveSheet()->SetCellValue($col.($i+1), str_replace(array('_X','!'),'',strtoupper($k)));
                    }
                    $xls->getActiveSheet()->SetCellValue($cel, str_replace('<br/>',' ',$v));
                } catch (Exception $e){
                }
                $j++;
            }
        }
        $xls->getActiveSheet()->setTitle($title);
        //Generate filename
        $filename  = '/export/';
        $filename .= str_replace(' ','-',strtolower($title));
        $filename .= date('-Y-m-d-H-i-s');
        $filename .= '.xlsx';
        //Init writer
        $writer = new \PHPExcel_Writer_Excel2007($xls);
        //Write
        $writer->save($_SERVER['DOCUMENT_ROOT'].$filename);
        //return filename
        return $filename;
    }
    
    public function getError()
    {
        return implode("\n",$this->error);
    }
    
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }
    
    public function setLineEnding($linending)
    {
        $this->lineending = $linending;
    }
}
