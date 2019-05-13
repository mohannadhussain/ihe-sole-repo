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
        $this->logger->info("Data submission: ", $data);
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
        
        // Transform in prep for DB storage
        $dbEvent = $this->sole2db($event);
        $auditMsg = null;
        
        // If the message is in XML, then we must process auxiliary objects
        // TODO find a better way to detect XML
        if( stripos($dbEvent['message'], '"<?xml') !== FALSE )
        {            
            $auditMsg = new SimpleXMLElement($dbEvent['message']);
            
            if( $auditMsg->AuditSourceIdentification ) 
            {
                $dbEvent['audit_source_uid'] = $this->getOrAddAuditSource($auditMsg->AuditSourceIdentification);
            }
            
            if( $auditMsg->EventIdentification ) 
            {
                $dbEvent['outcome_indicator'] = $auditMsg->EventOutcomeIndicator;
                // TODO: Store EventActionCode?!?
            }
        }
        
        // Insert into the DB
        $sql = "INSERT INTO event (audit_source_uid, priority, version, timestamp_message, hostname, 
                app_name, process_id, message_id, message, raw_submission, outcome_indicator) 
            VALUES (:audit_source_uid, :priority, :version, :timestamp_message, :hostname, 
                :app_name, :process_id, :message_id, :message, :raw_submission, :outcome_indicator);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':audit_source_uid', $dbEvent['audit_source_uid']);
        $stmt->bindValue(':priority', $dbEvent['priority']);
        $stmt->bindValue(':version', $dbEvent['version']);
        $stmt->bindValue(':timestamp_message', $dbEvent['timestamp_message']);
        $stmt->bindValue(':hostname', $dbEvent['hostname']);
        $stmt->bindValue(':app_name', $dbEvent['app_name']);
        $stmt->bindValue(':process_id', $dbEvent['process_id']);
        $stmt->bindValue(':message_id', $dbEvent['message_id']);
        $stmt->bindValue(':message', $dbEvent['message']);
        $stmt->bindValue(':raw_submission', $rawSubmission);
        $stmt->bindValue(':outcome_indicator', $dbEvent['outcome_indicator']);
        $result = $stmt->execute();
        $event_uid = $this->db->lastInsertId();
        
        // TODO check for and process auxiliary objects (patient, machine, location..etc)
        if( $auditMsg != '' ) 
        {
            
        }
    }
    
    private function getOrAddAuditSource($auditSource)
    {
        $code = $this->db->quote(filter_var($auditSource->code, FILTER_SANITIZE_STRING));
        $sourceId = $this->db->quote(filter_var($auditSource->AuditSourceID, FILTER_SANITIZE_STRING));
        $enterpriseSiteId = $this->db->quote(filter_var($auditSource->AuditEnterpriseSiteID, FILTER_SANITIZE_STRING));
        
        $stmt = $this->db->query("SELECT uid FROM audit_source 
            WHERE source_id={$sourceId} AND enterprise_site_id={$enterpriseSiteId} 
                AND source_type_code={$code};");
        $uid = $stmt->fetchColumn(0);
        if( $uid != '' ) return $uid;
        
        $sql = "INSERT INTO audit_source (source_id, enterprise_site_id, source_type_code) 
            VALUES (:source_id, :enterprise_site_id, :source_type_code);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':source_id', $sourceId);
        $stmt->bindValue(':enterprise_site_id', $enterpriseSiteId);
        $stmt->bindValue(':source_type_code', $code);
        $result = $stmt->execute();
        
        return $this->db->lastInsertId();
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
