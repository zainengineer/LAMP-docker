<?php

Class DockerBuilder
{
    protected $_bDaemon = false;
    protected $_bSudo = false;
    protected $_bBuild = false;
    protected $_bOverride = false;
    protected $_bCompose = false;
    protected $_bListIps = false;
    protected $_vOverRidePath;
    protected $_vRelativePath;
    protected $_aCustomConfig = [];

    public function __construct()
    {
        $this->configure();
    }

    protected function getCustomConfig()
    {
        $vPath = $this->getOverRidePath() . '/config.json';
        if (file_exists($vPath)) {
            $this->_aCustomConfig = @json_decode(file_get_contents($vPath),true) ?: [];
        }
    }
    protected function configure()
    {
        $aShortMap = [
            'd' => '_bDaemon',
            'b' => '_bBuild',
            'o' => '_bOverride',
            'c' => '_bCompose',
            'l' => '_bListIps',
            's' => '_bSudo',
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
        //needs to be at the end otherwise _bOverride param does not work
        $this->getCustomConfig();
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
            'sudo'         => 'sudo',
            'compose'      => '/usr/local/bin/docker-compose',
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
        if (!$this->_bSudo) {
            unset($aParts['sudo']);
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
        $vNewContent = $this->replaceString($vNewContent);
        $vNewContent .= $this->dockerAdditionalLines();
        $vNewContent .= "\n";
        return $vNewContent;
    }
    protected function replaceString($vContent)
    {
        $aReplaceMap = $this->getReplaceMap();
        if (!$aReplaceMap){
            return $vContent;
        }
        return str_replace(array_keys($aReplaceMap),$aReplaceMap,$vContent);
    }
    protected function getReplaceMap()
    {
        return isset($this->_aCustomConfig['replace']) ? $this->_aCustomConfig['replace']:  [];
    }
    protected function dockerAdditionalLines()
    {
        $aAppend = isset($this->_aCustomConfig['append'])? $this->_aCustomConfig['append']:  [];
        $aLines = empty($aAppend['lines']) ? [] : $aAppend['lines'];
        $aLines = is_array($aLines) ? $aLines : [];
        $vLines =  implode("\n", $aLines);
        return $vLines ? "\n" . $vLines : false;
    }

    protected function DockerFrom()
    {
        return isset($this->_aCustomConfig['from'])? $this->_aCustomConfig['from']:  '';
    }
    protected function getIpMap()
    {
        return isset($this->_aCustomConfig['ip_map'])? $this->_aCustomConfig['ip_map']:  [];
    }

    protected function getExistingDockerContent()
    {
        return file_get_contents($this->getDockerRepoPath() . '/Dockerfile');
    }
    protected function createSymLink()
    {
        if ($this->_bOverride){
            $vOverRidePath = $this->getOverRidePath() . '/docker-compose.yml';
            $vRepoPath = $this->getDockerRepoPath() .'/docker-compose.yml';
            if (file_exists($vOverRidePath)){
                unlink($vOverRidePath);
            }
            symlink($vRepoPath,$vOverRidePath);
        }
    }

    protected function getDockerComposeCommand()
    {
        $this->createSymLink();
        return implode(' ', $this->getCommandParts());
    }

    protected function getIpListCommandRaw()
    {
        $vOverRidePath = $this->getOverRidePath();
        $vOutput = "cd {$vOverRidePath} \n";
        $vOutput .= file_get_contents($this->ipScriptPath());
        return $vOutput;
    }

    protected function ipScriptPath()
    {
        return dirname(dirname(__FILE__)) . '/util/list_ips.sh';
    }

    protected function getIpListCommand()
    {
        $vReturn = $this->getIpListCommandRaw();
        if ($aIpMap = $this->getIpMap()) {
            $vOutput = $this->getIpOutput();
            $vFriendly = $this->convertIpOutputToFriendly($vOutput, $aIpMap);
            $vReturn .= "\n$vFriendly";
        }
        return $vReturn;
    }

    protected function convertIpOutputToFriendly($vOutput, $aIpMap)
    {
        $vOutput = str_replace(['ip of /', ' is'], '', $vOutput);
        $aRows = explode("\n", $vOutput);
        $vFriendly = "";
        foreach ($aRows as $vRow) {
            $vRow = trim($vRow);
            $aParts = explode(' ', $vRow);
            $vMachine = @$aParts[0];
            if (isset($aIpMap[$vMachine])) {
                $vDomain = $aIpMap[$vMachine];
                $vIp = trim(@$aParts[1]);
                $vFriendly .= "echo $vIp $vDomain\n";
            }
        }
        return $vFriendly;
    }

    protected function getIpOutput()
    {
        $vCurrentDir = getcwd();
        chdir($this->getOverRidePath());
        $cmd = 'bash ' . $this->ipScriptPath();
        $output = shell_exec($cmd);
        chdir($vCurrentDir);
        return $output;
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