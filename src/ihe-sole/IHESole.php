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
        'Comment' => 'comment',
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
        if( stripos($dbEvent['message'], '<?xml') !== FALSE )
        {
            $this->logger->info("XML message: ".$dbEvent['message']);
            try {
                $auditMsg = new SimpleXMLElement($dbEvent['message']);
            } catch( Exception $e ) {
                throw new BadMethodCallException("XML message not properly formatted");
            }
            
            if( $auditMsg->AuditSourceIdentification ) 
            {
                $dbEvent['audit_source_uid'] = $this->getOrAddAuditSource($auditMsg->AuditSourceIdentification);
            }
            
            if( $auditMsg->EventIdentification ) 
            {
                $dbEvent['outcome_indicator'] = $auditMsg->EventIdentification['EventOutcomeIndicator'];
                // TODO: Store EventActionCode?!?
            }
        }
        
        // Insert into the DB
        $sql = "INSERT INTO event (audit_source_uid, priority, version, timestamp_message, hostname, 
                app_name, process_id, message_id, comment, message, raw_submission, outcome_indicator) 
            VALUES (:audit_source_uid, :priority, :version, :timestamp_message, :hostname, 
                :app_name, :process_id, :message_id, :comment, :message, :raw_submission, :outcome_indicator);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':audit_source_uid', $dbEvent['audit_source_uid']);
        $stmt->bindValue(':priority', $dbEvent['priority']);
        $stmt->bindValue(':version', $dbEvent['version']);
        $stmt->bindValue(':timestamp_message', $dbEvent['timestamp_message']);
        $stmt->bindValue(':hostname', $dbEvent['hostname']);
        $stmt->bindValue(':app_name', $dbEvent['app_name']);
        $stmt->bindValue(':process_id', $dbEvent['process_id']);
        $stmt->bindValue(':message_id', $dbEvent['message_id']);
        $stmt->bindValue(':comment', $dbEvent['comment']);
        $stmt->bindValue(':message', $dbEvent['message']);
        $stmt->bindValue(':raw_submission', $rawSubmission);
        $stmt->bindValue(':outcome_indicator', $dbEvent['outcome_indicator']);
        $result = $stmt->execute();
        $event_uid = $this->db->lastInsertId();
        
        // TODO check for and process auxiliary objects (patient, machine, location..etc)
        if( $auditMsg != null && $auditMsg != '' ) 
        {
            if( $auditMsg->EventIdentification && $auditMsg->EventIdentification->EventTypeCode ) 
            {
                $this->linkUpTypeCode($auditMsg->EventIdentification->EventTypeCode, $event_uid);
            }
            
            for( $i=0; $auditMsg->ActiveParticipant && $i < $auditMsg->ActiveParticipant->count(); $i++ )
            {
                $curActiveParticipant = $auditMsg->ActiveParticipant[$i];
                $attributes = $curActiveParticipant->attributes();
                if( (string) $attributes['NetworkAccessPointID'] != '' ) 
                {
                    $this->linkUpMachine($curActiveParticipant, $event_uid);
                }
                elseif( (string) $attributes['UserID'] != '' )
                {
                    $this->linkUpPerson($curActiveParticipant, $event_uid);
                }
            }
        }
    }
    
    private function getOrAddAuditSource($auditSource)
    {
        $code = (string) $auditSource['code'];
        $sourceId = $auditSource['AuditSourceID'];
        $enterpriseSiteId = (string) $auditSource['AuditEnterpriseSiteID'];
        
        $sql = sprintf(
                "SELECT uid FROM audit_source WHERE source_id=%s 
                    AND enterprise_site_id=%s AND source_type_code=%s;",
                $this->db->quote(filter_var($sourceId, FILTER_SANITIZE_STRING)),
                $this->db->quote(filter_var($enterpriseSiteId, FILTER_SANITIZE_STRING)),
                $this->db->quote(filter_var($code, FILTER_SANITIZE_STRING)));
        $stmt = $this->db->query($sql);
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
    
    private function getOrAddTypeCode($eventTypeCode)
    {
        $typeCode = $eventTypeCode['csd-code'];
        $sql = sprintf(
                "SELECT uid FROM type_code WHERE type_code=%s;",
                $this->db->quote(filter_var($typeCode, FILTER_SANITIZE_STRING)));
        $stmt = $this->db->query($sql);
        $uid = $stmt->fetchColumn(0);
        if( $uid != '' ) return $uid;
        
        $codeSystemName = $eventTypeCode['codeSystemName'];
        $originalText = $eventTypeCode['originalText'];
        $sql = "INSERT INTO type_code (type_code, code_system_name, original_text) 
            VALUES (:type_code, :code_system_name, :original_text);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':type_code', $typeCode);
        $stmt->bindValue(':code_system_name', $codeSystemName);
        $stmt->bindValue(':original_text', $originalText);
        $result = $stmt->execute();
        
        return $this->db->lastInsertId();
    }
    
    private function linkUpTypeCode($eventTypeCode, $event_uid) 
    {
        $type_code_uid = $this->getOrAddTypeCode($eventTypeCode);
        $sql = "INSERT INTO event_type_code_map (event_uid, type_code_uid) 
            VALUES (:event_uid, :type_code_uid);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':event_uid', $event_uid);
        $stmt->bindValue(':type_code_uid', $type_code_uid);
        $result = $stmt->execute();
    }
    
    private function getOrAddMachine($ActiveParticipant) 
    {
        //$this->logger->info(var_export($ActiveParticipant, 1));
        $roleId = '';
        $userId = $ActiveParticipant['UserID'];
        
        for( $i=0; $ActiveParticipant->RoleIDCode && $i < $ActiveParticipant->RoleIDCode->count(); $i++ )
        {
            $curRoleIdCode = $ActiveParticipant->RoleIDCode[$i];
            if( $curRoleIdCode['codeSystemName'] == 'IHE-SOLE' ) 
            {
                $roleId = $curRoleIdCode['csd-code'];
            }
        }
        
        $sql = sprintf(
                "SELECT uid FROM machine WHERE user_id=%s AND role_id_code=%s;",
                $this->db->quote(filter_var($userId, FILTER_SANITIZE_STRING)),
                $this->db->quote(filter_var($roleId, FILTER_SANITIZE_STRING)));
        $stmt = $this->db->query($sql);
        $uid = $stmt->fetchColumn(0);
        if( $uid != '' ) return $uid;
        
        $netTypeCode = $ActiveParticipant['NetworkAccessPointTypeCode'];
        $netPointId = $ActiveParticipant['NetworkAccessPointID'];
        $sql = "INSERT INTO machine (user_id, role_id_code, network_access_point_type_code, network_access_point_id) 
            VALUES (:user_id, :role_id_code, :network_access_point_type_code, :network_access_point_id);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':role_id_code', $roleId);
        $stmt->bindValue(':network_access_point_type_code', $netTypeCode);
        $stmt->bindValue(':network_access_point_id', $netPointId);
        $result = $stmt->execute();
        
        return $this->db->lastInsertId();
    }
    
    private function linkUpMachine($ActiveParticipant, $event_uid)
    {
        $machine_uid = $this->getOrAddMachine($ActiveParticipant);
        $sql = "INSERT INTO event_machine_map (event_uid, machine_uid) 
            VALUES (:event_uid, :machine_uid);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':event_uid', $event_uid);
        $stmt->bindValue(':machine_uid', $machine_uid);
        $result = $stmt->execute();
    }
    
    private function getOrAddPerson($ActiveParticipant) 
    {
        $roleId = '';
        $userId = $ActiveParticipant['UserID'];
        
        /*for( $i=0; $ActiveParticipant->RoleIDCode && $i < $ActiveParticipant->RoleIDCode->count(); $i++ )
        {
            $curRoleIdCode = $ActiveParticipant->RoleIDCode[$i];
            if( $curRoleIdCode['codeSystemName'] == 'IHE-SOLE' ) 
            {
                $roleId = $curRoleIdCode['csd-code'];
            }
        }*/
        
        $sql = sprintf(
                "SELECT uid FROM person WHERE user_id=%s;",
                $this->db->quote(filter_var($userId, FILTER_SANITIZE_STRING)));
        $stmt = $this->db->query($sql);
        $uid = $stmt->fetchColumn(0);
        if( $uid != '' ) return $uid;
        
        
        $user_is_requestor = strtolower($ActiveParticipant['UserIsRequestor']) == 'true' ? true:false;
        //TODO figure out how to map role codeID, department and shift
        $sql = "INSERT INTO person (user_id, user_is_requestor) 
            VALUES (:user_id, :user_is_requestor);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':user_is_requestor', $user_is_requestor);
        $result = $stmt->execute();
        
        return $this->db->lastInsertId();
    }
    
    private function linkUpPerson($ActiveParticipant, $event_uid)
    {
        $person_uid = $this->getOrAddPerson($ActiveParticipant);
        $sql = "INSERT INTO event_person_map (event_uid, person_uid) 
            VALUES (:event_uid, :person_uid);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':event_uid', $event_uid);
        $stmt->bindValue(':person_uid', $person_uid);
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
