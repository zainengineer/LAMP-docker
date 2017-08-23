<?php
require_once dirname(__FILE__) . '/DockerBuilder.php';
$vOverRide = dirname(dirname(dirname(__FILE__))) . '/DockerBuilderOverride.php';
if (file_exists($vOverRide)){
    require_once $vOverRide;
}
else{
    require_once dirname(__FILE__) . '/DockerBuilderOverride.php';
}
$builder = new DockerBuilderOverride();
echo $builder->executeAction();