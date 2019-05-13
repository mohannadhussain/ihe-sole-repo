<?php

use Slim\Http\Request;
use Slim\Http\Response;
require("ihe-sole/IHESole.php");

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Invoked '/' route - serving index.phtml");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/bulk-syslog-events', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Invoked '/bulk-syslog-events' route");
    
    // validate HTTP headers
    $contentType = $request->getHeaderLine('Content-Type');
    $accept = $request->getHeaderLine('Accept');
    if( $contentType != 'application/json' || $accept != 'application/json' )
    {
        return $response->withStatus(400, "Content-Type and Accept headers both must have a value of application/json");
    }

    $rawSubmission = $request->getBody()->getContents();
    $this->logger->info("rawSubmission: ". var_export($rawSubmission,1));
    $json = json_decode($rawSubmission, true);
    if( $json == null )
    {
        return $response->withStatus(400, "Submitted JSON data was NOT properly formatted");
    }
    
    try {
        (new IHESole($this->db, $this->logger))->storeBulkEvents($json, $rawSubmission);
        
    } catch( BadMethodCallException $e ) { // BadMethodCallException signals an exception that is OK to communicate to the end user
        $this->logger->warn("Caught Exception, message is ".$e->getMessage());
        return $response->withStatus(400, $e->getMessage());
  
    } catch( Exception $e ) { // all other types of exceptions are assumed not OK to communicate to the user
        $this->logger->warn("Caught Exception, message is ".$e->getMessage());
        return $response->withStatus(500, "Internal server error, check the server logs for more information");
    }

    // Signal success!
    return $response->withStatus(204, "");
});