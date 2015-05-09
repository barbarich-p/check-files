<?php

class File_Compare
{
    private $_delSources = true;

    private $_template = '<div class="filename" style="text-align: center" ><h1>{path}</h1></div><div class="diff" >{content}</div>';

    private $_differOptions = array(
        'ignoreWhitespace' => true,
        'ignoreNewLines' => true,
        'ignoreCase' => false,
    );
    private $_fileTypes = array();
    private $_currentPath;
    private $_cleanSourcesPath;

    public function __construct($fileTypes)
    {
        $this->_fileTypes = $fileTypes;
        $this->_currentPath = dirname(__FILE__);
        $this->_cleanSourcesPath = $this->_currentPath . DIRECTORY_SEPARATOR . 'cleanSources/';
        mkdir($this->_cleanSourcesPath );
    }

    /**
     * @param boolean $delSources
     */
    public function setDelSources($delSources)
    {
        $this->_delSources = $delSources;
    }

    /**
     * @param array $differOptions
     */
    public function setDifferOptions($differOptions)
    {
        $this->_differOptions = $differOptions;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->_template = $template;
    }

    /**
     * @param $path
     * @return array|RegexIterator
     */
    private function _getFilesArray($path)
    {
        $dirIterator = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);
        $typesString = implode("|", $this->_fileTypes);
        $files = new RegexIterator($iterator, '/([^\s]+(\.(?i)(' . $typesString . '))$)/i', RecursiveRegexIterator::GET_MATCH);
        $files = iterator_to_array($files);
        $files = array_keys($files);
        return $files;
    }

    /**
     * @param $file
     * @throws Exception
     */
    private function _uploadAndUnzip($file)
    {
        $this->_delTree($this->_cleanSourcesPath);
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {
            // extract it to the path we determined above
            $zip->extractTo($this->_cleanSourcesPath);
            $zip->close();
        } else {
            throw new Exception('Can\'t unzip source file');
        }
    }

    /**
     * @param $dir
     */
    private function _delTree($dir)
    {
        $it = new RecursiveDirectoryIterator($dir);
        $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ('.' === $file->getBasename() || '..' === $file->getBasename()) continue;
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($dir);
    }

    /**
     * @param $serverFilePath
     * @param $cleanFilePath
     * @return string
     */
    private function _compareFiles($serverFilePath,$cleanFilePath)
    {
        $serverFile = $this->_getFileContent( $serverFilePath );
        $cleanFile = $this->_getFileContent($cleanFilePath);
        return  str_replace( "\r", PHP_EOL, $this->_getDiffHtml($cleanFile, $serverFile,$serverFilePath) );
    }

    /**
     * @param $path
     * @return array|mixed
     */
    private function _getFileContent($path)
    {
        $fileContent = explode(PHP_EOL, file_get_contents( $path ) );
        if ( count( $fileContent<2 ) ) $fileContent = explode("\r", file_get_contents( $path ) );
        if ( count( $fileContent<2 ) ) $fileContent = explode("\n", file_get_contents( $path ) );
        $fileContent = str_replace("\r\n", "\r", $fileContent);
        $fileContent = str_replace("\n", "\r", $fileContent);
        return $fileContent;
    }

    /**
     * @param $cleanFile
     * @param $serverFile
     *
     * @return string
     */
    private function _getDiffHtml($cleanFile, $serverFile,$cleanFilePath)
    {
        // Initialize the diff class
        $diff = new Diff($cleanFile, $serverFile, $this->_differOptions);
        $renderer = new Diff_Renderer_Html_SideBySide();
        $diffHtml = $diff->Render($renderer);
        $template =  str_replace( '{content}',$diffHtml, $this->_template );
        $template =  str_replace( '{path}',$cleanFilePath, $template );
        return $template;
    }

    /**
     * @param $file
     * @return string
     * @throws Exception
     */
    public function process($file)
    {
        try {
            $this->_uploadAndUnzip($file);
            $originalFiles = $this->_getFilesArray($this->_currentPath . DIRECTORY_SEPARATOR . 'cleanSources');
            $html = '';
            foreach ($originalFiles as $originalFile) {
                $originalMd5 = md5_file($originalFile);
                $currentFileParts = explode('cleanSources', $originalFile);
                $currentFilePath = dirname(dirname(__FILE__)) . $currentFileParts[1];
                $currentMd5 = md5_file($currentFilePath);
                if ($originalMd5 != $currentMd5) {
                    $html .= $this->_compareFiles($currentFilePath, $originalFile);
                }
            }
            if ($this->_delSources){
                $this->_delTree( $this->_cleanSourcesPath );
            }
            return $html;
        }catch (Exception $e){
            echo $e->getMessage();
            if ($this->_delSources){
                $this->_delTree( $this->_cleanSourcesPath );
            }
        }
    }

}
