<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of IHESole
 *
 * @author Mohannad Hussain
 */
class IHESole {
    private $db;
    private $logger;
    /**
     * Maps out SOLE event attributes to DB column names
     * @var type Array (associative)
     */
    private $attributeMapping = array(
        'Pri' => 'priority',
        'Version' => 'version',
        'Timestamp' => 'timestamp_message',
        'Hostname' => 'hostname',
        'App-name' => 'app_name',
        'Procid' => 'process_id',
        'Msg-id' => 'message_id',
        'Msg' => 'message',
    );
    
    /**
     * 
     * @param type $db Database connection
     * @param type $logger Application log adapter
     */
    public function __construct($db, $logger) 
    {
        $this->db = $db;
        $this->logger = $logger;
    }
        
    /**
     * Stores bulk (1...n) event submissions
     * 
     * @param array $data Associative array of events and their attributes - usually parsed form the HTTP post submission
     * @param string $rawSubmission Raw HTTP POST submission body
     */
    public function storeBulkEvents($data, $rawSubmission)
    {
        $this->loger->trace("Data submission: ", $data);
        $events = $data['Events'];
        
        if( !is_array($events) || count($events) < 1 ) 
        {
            throw new BadMethodCallException("No events in the submitted data");
        }
        
        foreach( $events as $event )
        {
            $this->storeEvent($event, $rawSubmission);
        }
        return true;
    }
    
    /**
     * Stores a single SOLE Event
     * 
     * @param array $event JSON event
     * @param string $rawSubmission Raw HTTP POST submission body
     */
    public function storeEvent($event, $rawSubmission) 
    {
        // TODO validate the bare minimum of event attributes
        
        // TODO check for and process auxiliary objects (patient, machine, location..etc)
        
        // Transform in prep for DB storage
        $dbEvent = $this->sole2db($event);
        
        // Insert into the DB
        $sql = "INSERT INTO event (priority, version, timestamp_message, hostname, 
                app_name, process_id, message_id, message, raw_submission) 
            VALUES (:priority, :version, :timestamp_message, :hostname, 
                :app_name, :process_id, :message_id, :message, :raw_submission);";
        $stmt = $app->db->prepare($sql);
        $stmt->bindValue(':priority', $dbEvent['priority']);
        $stmt->bindValue(':version', $dbEvent['version']);
        $stmt->bindValue(':timestamp_message', $dbEvent['timestamp_message']);
        $stmt->bindValue(':hostname', $dbEvent['hostname']);
        $stmt->bindValue(':app_name', $dbEvent['app_name']);
        $stmt->bindValue(':process_id', $dbEvent['process_id']);
        $stmt->bindValue(':message_id', $dbEvent['message_id']);
        $stmt->bindValue(':message', $dbEvent['message']);
        $stmt->bindValue(':raw_submission', $rawSubmission);
        $result = $stmt->execute();
    }
    
    /**
     * Transforms an event's attribute names from JSON to DB column names
     * @param type $event
     */
    private function sole2db($event) 
    {
        return $this->transformArrayKeys($event, $this->attributeMapping);
    }
    
    /**
     * Does the reverse of sole2db()
     * 
     * @param type $event
     * @see sole2db()
     */
    private function db2sole($event)
    {
        return $this->transformArrayKeys($event, array_flip($this->attributeMapping));
    }
    
    private function transformArrayKeys($in, $map) 
    {
        $out = array();
        foreach( $in as $keyOld => $val )
        {
            $keyNew = $map[$keyOld];
            if( $keyNew ) {
                $out[$keyNew] = $val;
            }
        }
        return $out;
    }
}
