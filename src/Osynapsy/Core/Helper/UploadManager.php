<?php
namespace Osynapsy\Core\Helper;

class UploadManager
{    
    private $documentRoot;    
    
    public function __construct()
    {                
        $this->documentRoot = filter_input(\INPUT_SERVER, 'DOCUMENT_ROOT');
    }

    public function getUniqueFilename($pathOnDisk)
    {
        if (empty($pathOnDisk)) {
            return false;
        }
        //Se il Path non eiste su disco lo restituisco.
        if (!file_exists($pathOnDisk)) {
            return $pathOnDisk;
        } 
        $pathInfo = pathinfo($pathOnDisk);
        $i = 1;
        while (file_exists($pathOnDisk)) {
            $pathOnDisk = $pathInfo['dirname'].'/'.$pathInfo['filename'].'_'.$i.'.'.$pathInfo['extension'];
            $i++;
        }
        return $pathOnDisk;
    }
    
    private function checkUploadDir($uploadRoot)
    { 
        if (empty($uploadRoot)){
            return 'configuration parameters.path-upload is empty';
        }
        if (!is_dir($this->documentRoot.$uploadRoot)) {
            return 'path-upload '.$this->documentRoot.$uploadRoot.' not exists';
        } 
        if (!is_writeable($this->documentRoot.$uploadRoot)) {
            return $this->documentRoot.$uploadRoot.' is not writeable.';
        }        
    }
    
    public function saveFile($componentName, $uploadRoot='/upload/')
    {
        if (!is_array($_FILES) || !array_key_exists($componentName, $_FILES)){ 
            return; 
        }           
        $fileNameFinal = $_FILES[$componentName]['name'];
        $fileNameTemp = $_FILES[$componentName]['tmp_name'];
        $alert = $this->checkUploadDir($uploadRoot);
        if (!empty($alert)) {
            throw new \Exception('path-upload '.$this->documentRoot.$uploadRoot.' not exists');            
        } elseif(empty($fileNameFinal)) {             
            throw new \Exception('Filename is empty for field '.$componentName);
        } elseif (empty($fileNameTemp)) {            
            throw new \Exception('Temporary filename is empty for field '.$componentName);
        }       
        $pathOnWeb = $uploadRoot.'/'.$fileNameFinal;
        $pathOnDisk = $this->getUniqueFilename($this->documentRoot.$pathOnWeb);
        $pathOnWeb = str_replace($this->documentRoot,'',$pathOnDisk);
        //Thumbnail path            
        if ($pathOnDisk && move_uploaded_file($fileNameTemp, $pathOnDisk)){                        
            //Inserisco sul db l'immagine
            return $pathOnWeb;           
        }
    }
}
