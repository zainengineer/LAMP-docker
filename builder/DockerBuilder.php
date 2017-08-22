<?php

Class DockerBuilder
{
    protected $_bDaemon = false;
    protected $_bBuild = false;
    protected $_bOverride = false;
    protected $_bCompose = false;
    protected $_bListIps = false;
    protected $_vOverRidePath;
    protected $_vRelativePath;

    public function __construct()
    {
        $this->configure();
    }

    protected function configure()
    {
        $aShortMap = [
            'd' => '_bDaemon',
            'b' => '_bBuild',
            'o' => '_bOverride',
            'c' => '_bCompose',
            'l' => '_bListIps',
        ];
        $vOptions = implode(":",array_keys($aShortMap)) . ":";
        $aOptions = getopt($vOptions);

        foreach ($aShortMap as $key=>$variable) {
            if (isset($aOptions[$key])){
                if (!property_exists($this,$variable)){
                    throw new Exception("property $variable of " . get_class($this) . " does not exists");
                }
                $this->$variable = ($aOptions[$key] == '1');
            }
        }
    }

    protected function getProjectName()
    {
        //issues with docker-compose ps
        return '';
        return basename(dirname($this->getOverRidePath()));
    }

    protected function getDockerRepoPath()
    {
//        return dirname(dirname(__FILE__)) . '/LAMP-docker';
        return dirname(dirname(__FILE__));
    }

    protected function getOverRidePath()
    {
        if (is_null($this->_vOverRidePath)) {
            if ($this->_bOverride){
                $this->_vOverRidePath = dirname(dirname(dirname(__FILE__)));
            }
            else{
                $this->_vOverRidePath = $this->getDockerRepoPath();
            }
        }
        return $this->_vOverRidePath;
    }

    protected function getDockerComposePath()
    {
        return $this->getDockerRepoPath() . '/docker-compose.yml';
    }

    protected function getDockerComposeOverridePath()
    {
        return $this->getOverRidePath() . '/docker-compose.override.yml';
    }

    protected function getCommandParts()
    {
        $this->reBuiltDockerFile();
        $vProjectName = $this->getProjectName();
        $vOverRidePath = $this->getOverRidePath();
        $vComposePath = $this->getDockerComposePath();
        $vComposeOverridePath = $this->getDockerComposeOverridePath();
        $aParts = [
            //            'cd' => "cd $vRepoPath &&",
            'compose'      => 'docker-compose',
            'main-yml'     => "-f $vComposePath",
            'override-yml' => "-f $vComposeOverridePath",
            'project-path' => "--project-directory $vOverRidePath",
            'project-name' => "--project-name $vProjectName",
            'daemon'       => "-d",
            'command'      => "up",
            'build'        => "--build",
        ];
        if (!$this->_bDaemon) {
            unset($aParts['daemon']);
        }
        if (!$this->_bBuild) {
            unset($aParts['build']);
        }
        if (!$this->_bOverride || (!$vProjectName)){
            unset($aParts['project-name']);
        }
        return $aParts;
    }

    protected function reBuiltDockerFile()
    {
        $vRebuildPath = $this->dockerRebuildPath();
        if (!$vRebuildPath) {
            return;
        }
        $vContents = $this->getDockerContent();
        file_put_contents($vRebuildPath, $vContents);
    }

    protected function dockerRebuildPath()
    {
        $vOverRidePath = $this->getOverRidePath();
        $vRepoPath = $this->getDockerRepoPath();
        if ($vOverRidePath == $vRepoPath) {
            return '';
        }
        return $vOverRidePath . '/DockerOverridefile';
    }

    protected function getDockerContent()
    {
        $vExistingContent = $this->getExistingDockerContent();
        return $this->replaceContent($vExistingContent);
    }

    protected function getRepoRelativePath()
    {
//        if (is_null($this->_vRelativePath)){
//            $vRepoPath = $this->getDockerRepoPath();
//            $vOverRidePath = $this->getOverRidePath();
//            if ($vRepoPath == $vOverRidePath) {
//                $this->_vRelativePath = '';
//            }
//            else{
//                $vParentPath = $vOverRidePath;
//                $iMaxCount = 10;
//                $iCount = 0;
//                while (strpos($vRepoPath, $vParentPath) === false) {
//                    $vParentPath = dirname($vParentPath);
//                    $iCount++;
//                    if ($iCount > $iMaxCount) {
//                        break;
//                    }
//                }
//                $this->_vRelativePath = str_repeat("../",$iCount);
//            }
//        }
        return "LAMP-docker/";
    }

    protected function replaceContent($vExistingContent)
    {
        $vFrom = $this->DockerFrom();
        $aParts = explode("\n", $vExistingContent);
        $aPartsCopy = array();
        $vRelativePath = $this->getRepoRelativePath();
        foreach ($aParts as $vPart) {
            if (strpos($vPart, 'FROM') === 0) {
                if ($vFrom) {
                    $vPart = 'FROM ' . $vFrom;
                }
            }
            elseif (strpos($vPart, 'ADD ') === 0) {
                $vMain = substr($vPart, 4);
                $vPart = "ADD $vRelativePath{$vMain}";
            }

            $aPartsCopy[] = $vPart;
        }
        $vNewContent = implode("\n", $aPartsCopy);
        $vNewContent .= "\n";
        return $vNewContent;
    }

    protected function DockerFrom()
    {
        return '';
    }

    protected function getExistingDockerContent()
    {
        return file_get_contents($this->getDockerRepoPath() . '/Dockerfile');
    }
    protected function createSymLink()
    {
        if ($this->_bOverride){
            $vOverRidePath = $this->getOverRidePath() . '/docker-compose.yml';
            if (file_exists($vOverRidePath)){
                unlink($vOverRidePath);
            }
            symlink($this->getDockerRepoPath() .'/docker-compose.yml',$vOverRidePath);
        }
    }

    protected function getDockerComposeCommand()
    {
        $this->createSymLink();
        return implode(' ', $this->getCommandParts());
    }
    protected function getIpListCommand()
    {
        $vOverRidePath = $this->getOverRidePath();
        $vOutput = "cd {$vOverRidePath} \n";
        $vOutput .= file_get_contents(dirname(dirname(__FILE__)) . '/util/list_ips.sh');
        return $vOutput;
    }
    public function executeAction()
    {
        if ($this->_bCompose){
            return $this->getDockerComposeCommand();
        }elseif ($this->_bListIps){
            return $this->getIpListCommand();
        }
    }

}