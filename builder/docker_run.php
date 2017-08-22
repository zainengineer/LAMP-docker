<?php
require_once dirname(__FILE__) . '/DockerBuilder.php';
require_once dirname(__FILE__) . '/DockerBuilderOverride.php';
$builder = new DockerBuilderOverride();
echo $builder->executeAction();