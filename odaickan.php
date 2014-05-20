<?php
require 'Slim/Slim.php';
include 'ckan_converter.class.php';
\Slim\Slim::registerAutoloader ();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim ( array (
		'log.enabled' => true,
		'log.level' => \Slim\Log::DEBUG 
) );

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function.
 */

// --------------------------------------------------------------------------------
// /PACKAGE
// --------------------------------------------------------------------------------

$app->get ( '/package', function () {
	$fw = new \Slim\LogWriter ( @fopen ( 'log.txt', 'a' ) );
	$app = \Slim\Slim::getInstance ();
	$req = $app->request ();
	$headers = getallheaders ();
	$body = $req->getBody ();
	$fw->write ( print_r ( $body, true ) );
	$fw->write ( print_r ( $headers, true ) );
	
	$ckan = new Ckan_converter ( "http://10.118.8.67:9763", $user = 'admin', $password = 'admin', $debug = true );
	
	$result = $ckan->getPackages ();
	
	echo json_encode ( $result );
} );

// --------------------------------------------------------------------------------
// /PACKAGE/:NAME
// --------------------------------------------------------------------------------

$app->get ( '/package/:name', function ($name) {
	$fw = new \Slim\LogWriter ( @fopen ( 'log.txt', 'a' ) );
	$app = \Slim\Slim::getInstance ();
	$req = $app->request ();
	$headers = getallheaders ();
	$body = $req->getBody ();
	$fw->write ( print_r ( $body, true ) );
	$fw->write ( print_r ( $headers, true ) );
	
	$allGetVars = $req->get ();
	$fw->write ( print_r ( $allGetVars, true ) );
	$fw->write ( print_r ( $name, true ) );
	
	list ( $apiname, $apiversion, $apiprovider ) = split ( ':', $name );
	
	$ckan = new Ckan_converter ( "http://10.118.8.67:9763", $user = 'admin', $password = 'admin', $debug = true );
	
	$result = $ckan->getAPI ( $apiname, $apiversion, $apiprovider );
	
	$res = $app->response ();
	$res ['Content-Type'] = 'application/json';
	echo json_encode ( $result );
} );

// ------------------------------------------------
// NOT FOUND
// ------------------------------------------------

$app->notFound ( function () use($app) {
	// $app->render('404.html');
	$app = \Slim\Slim::getInstance ();
	$req = $app->request ();
	$fw = new \Slim\LogWriter ( @fopen ( 'log.txt', 'a' ) );
	$headers = getallheaders ();
	$body = $req->getBody ();
	$allGetVars = $req->get ();
	// Get root URI
	$rootUri = $req->getRootUri ();
	// Get resource URI
	$resourceUri = $req->getResourceUri ();
	$isAjax = $app->request ()->isAjax ();
	$isXHR = $app->request ()->isXhr ();
	$path = $req->getPath ();
	$url = $req->getUrl ();
	
	$fw->write ( 'body:' . print_r ( $body, true ) );
	$fw->write ( 'headers:' . print_r ( $headers, true ) );
	$fw->write ( 'allGetVars:' . print_r ( $allGetVars, true ) );
	$fw->write ( 'rootUri:' . print_r ( $rootUri, true ) );
	$fw->write ( 'resourceUri:' . print_r ( $resourceUri, true ) );
	$fw->write ( 'isAjax:' . print_r ( $isAjax, true ) );
	$fw->write ( 'isXHR:' . print_r ( $isXHR, true ) );
	$fw->write ( 'path:' . print_r ( $path, true ) );
	$fw->write ( 'url:' . print_r ( $url, true ) );
	$fw->write ( 'req:' . print_r ( $req, true ) );
	
	echo "not found";
} );

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run ();

?>