<?php
	use Doctrine\ORM\Tools\Setup;
	use Doctrine\ORM\EntityManager;
	date_default_timezone_set('America/Lima');
	require_once "vendor/autoload.php";
	$isDevMode = true;
	$config = Setup::createYAMLMetadataConfiguration(array(__DIR__ . "/config/yaml"), $isDevMode);
	$conn = array(
		'host' => 'dpg-cm23hgfqd2ns73d8du20-a.oregon-postgres.render.com',
	
		'driver' => 'pdo_pgsql',
		'user' => 'mabdd_user',
		'password' => 'GfH00fj6dSVF80m5zU6G91xqCprd8ONo',
		'dbname' => 'mabdd',
		'port' => '5432'
	);


	$entityManager = EntityManager::create($conn, $config);



