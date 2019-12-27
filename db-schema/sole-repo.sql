
CREATE SEQUENCE public.patient_uid_seq;

CREATE TABLE public.patient (
                uid BIGINT NOT NULL DEFAULT nextval('public.patient_uid_seq'),
                type_code VARCHAR NOT NULL,
                type_code_role VARCHAR NOT NULL,
                object_sensitivity VARCHAR,
                object_id VARCHAR NOT NULL,
                object_id_type_code VARCHAR NOT NULL,
                CONSTRAINT patient_pk PRIMARY KEY (uid)
);
COMMENT ON TABLE public.patient IS 'Patient participants are the patients';
COMMENT ON COLUMN public.patient.type_code IS '"1" (person)';
COMMENT ON COLUMN public.patient.type_code_role IS '“1” (patient)';
COMMENT ON COLUMN public.patient.object_id IS 'the patient ID in HL7 CX format.';
COMMENT ON COLUMN public.patient.object_id_type_code IS 'EV(121025, DCM, “Patient”)';


ALTER SEQUENCE public.patient_uid_seq OWNED BY public.patient.uid;

CREATE SEQUENCE public.location_uid_seq;

CREATE TABLE public.location (
                uid BIGINT NOT NULL DEFAULT nextval('public.location_uid_seq'),
                type_code VARCHAR NOT NULL,
                type_code_role VARCHAR NOT NULL,
                object_id VARCHAR NOT NULL,
                object_id_type_code VARCHAR NOT NULL,
                object_detail VARCHAR NOT NULL,
                CONSTRAINT location_pk PRIMARY KEY (uid)
);
COMMENT ON TABLE public.location IS 'Location participants are locations where events have taken place or activities are scheduled,
e.g., "Room 101". There are standard codes for geographic locations and addresses, but not for
the internal room naming system within the imaging facility. The location is identified by setting
two of the name-value pairs in the ParticipantObjectDetail. A name for the location encoding
 shall be specified, e.g., "St. Mary’s of Boston Clinic Rooms", and the name for the location
within that encoding shall be specified, e.g., "Grant CT Suite A".';
COMMENT ON COLUMN public.location.type_code IS '"3" (Organization)';
COMMENT ON COLUMN public.location.type_code_role IS '"2" (Location)';
COMMENT ON COLUMN public.location.object_id_type_code IS 'EV(SOLE51,urn:ihe:rad,"Location of Event")
or
EV(SOLE52, urn:ihe:rad, "Location assigned")';
COMMENT ON COLUMN public.location.object_detail IS '"Location"=<location-value-string>
"Location-encoding"=<name-for-location-encoding>';


ALTER SEQUENCE public.location_uid_seq OWNED BY public.location.uid;

CREATE SEQUENCE public.resource_uid_seq;

CREATE TABLE public.resource (
                uid BIGINT NOT NULL DEFAULT nextval('public.resource_uid_seq'),
                type_code VARCHAR NOT NULL,
                type_code_role VARCHAR NOT NULL,
                object_id VARCHAR NOT NULL,
                object_id_type_code VARCHAR NOT NULL,
                CONSTRAINT resource_pk PRIMARY KEY (uid)
);
COMMENT ON TABLE public.resource IS 'Resource participants are rooms, assigned machines, etc. that are the objects of actions.';
COMMENT ON COLUMN public.resource.type_code IS '"2" (system object)';


ALTER SEQUENCE public.resource_uid_seq OWNED BY public.resource.uid;

CREATE SEQUENCE public.person_uid_seq;

CREATE TABLE public.person (
                uid BIGINT NOT NULL DEFAULT nextval('public.person_uid_seq'),
                user_id VARCHAR NOT NULL,
                alt_user_id VARCHAR,
                user_name VARCHAR,
                user_is_requestor BOOLEAN,
                role_id_code VARCHAR,
                department VARCHAR,
                shift VARCHAR,
                CONSTRAINT person_pk PRIMARY KEY (uid)
);
COMMENT ON TABLE public.person IS 'Person Participants are staff that actively participate in the event, e.g., Radiologist or
Technologist.';
COMMENT ON COLUMN public.person.user_id IS 'One identity of the human that participated in the
transaction, e.g., Employee Number';
COMMENT ON COLUMN public.person.alt_user_id IS 'A second identity of the human that participated in the
transaction, e.g., NPI (US).';
COMMENT ON COLUMN public.person.user_name IS 'not specialized (DICOM PS3.15 Section A.5)';
COMMENT ON COLUMN public.person.user_is_requestor IS 'not specialized (DICOM PS3.15 Section A.5)';


ALTER SEQUENCE public.person_uid_seq OWNED BY public.person.uid;

CREATE SEQUENCE public.object_uid_seq;

CREATE TABLE public.object (
                uid BIGINT NOT NULL DEFAULT nextval('public.object_uid_seq'),
                type_code VARCHAR NOT NULL,
                type_code_role VARCHAR NOT NULL,
                object_id VARCHAR NOT NULL,
                object_id_type_code VARCHAR NOT NULL,
                CONSTRAINT object_pk PRIMARY KEY (uid)
);
COMMENT ON TABLE public.object IS 'Object Participants are software and conceptual objects, e.g., “order”, "study" and "report"';
COMMENT ON COLUMN public.object.type_code IS '"2" (system object)';
COMMENT ON COLUMN public.object.type_code_role IS '“3” (Report – for a study or a report)
“20” (Job – for an order)';


ALTER SEQUENCE public.object_uid_seq OWNED BY public.object.uid;

CREATE SEQUENCE public.machine_uid_seq;

CREATE TABLE public.machine (
                uid BIGINT NOT NULL DEFAULT nextval('public.machine_uid_seq'),
                user_id VARCHAR NOT NULL,
                alt_user_id VARCHAR,
                user_name VARCHAR,
                role_id_code VARCHAR NOT NULL,
                network_access_point_type_code VARCHAR NOT NULL,
                network_access_point_id VARCHAR NOT NULL,
                participant_object_detail VARCHAR,
                CONSTRAINT machine_pk PRIMARY KEY (uid)
);
COMMENT ON TABLE public.machine IS 'Machine participants are machines, software, applications, etc., that actively participate in the
event, e.g., Modality or Image Archive.';
COMMENT ON COLUMN public.machine.user_id IS 'Primary identity of the machine participant, e.g., process
ID, AE title, etc.';
COMMENT ON COLUMN public.machine.alt_user_id IS 'A second identity of the machine participant, e.g., process
ID, AE title, etc.';
COMMENT ON COLUMN public.machine.user_name IS 'not specialized (DICOM PS3.15 Section A.5)';
COMMENT ON COLUMN public.machine.network_access_point_type_code IS '"1" for machine name, "2" for IP address';
COMMENT ON COLUMN public.machine.network_access_point_id IS 'The machine name (in DNS) or IP address.';
COMMENT ON COLUMN public.machine.participant_object_detail IS '"Function=<name-of-function>"
ParticipantObjectDetail identifies the participating
internal functions when the machine has multiple
functions and not all participated in the event. e.g.,
"Function=CAD", "Function=Joe Algorithm"';


ALTER SEQUENCE public.machine_uid_seq OWNED BY public.machine.uid;

CREATE SEQUENCE public.audit_source_uid_seq;

CREATE TABLE public.audit_source (
                uid BIGINT NOT NULL DEFAULT nextval('public.audit_source_uid_seq'),
                source_id VARCHAR NOT NULL,
                enterprise_site_id VARCHAR NOT NULL,
                source_type_code VARCHAR NOT NULL,
                CONSTRAINT audit_source_pk PRIMARY KEY (uid)
);


ALTER SEQUENCE public.audit_source_uid_seq OWNED BY public.audit_source.uid;

CREATE UNIQUE INDEX source_id_idx
 ON public.audit_source
 ( source_id );

CREATE SEQUENCE public.type_code_uid_seq;

CREATE TABLE public.type_code (
                uid BIGINT NOT NULL DEFAULT nextval('public.type_code_uid_seq'),
                type_code VARCHAR NOT NULL,
                code_system_name VARCHAR,
                original_text VARCHAR,
                CONSTRAINT type_code_pk PRIMARY KEY (uid)
);


ALTER SEQUENCE public.type_code_uid_seq OWNED BY public.type_code.uid;

CREATE UNIQUE INDEX type_code_idx
 ON public.type_code
 ( type_code );

CREATE SEQUENCE public.event_uid_seq;

CREATE TABLE public.event (
                uid BIGINT NOT NULL DEFAULT nextval('public.event_uid_seq'),
                audit_source_uid BIGINT DEFAULT 0,
                priority VARCHAR NOT NULL,
                version INTEGER NOT NULL,
                timestamp_message TIMESTAMP NOT NULL,
                hostname VARCHAR NOT NULL,
                app_name VARCHAR NOT NULL,
                process_id VARCHAR NOT NULL,
                message_id VARCHAR NOT NULL,
                outcome_indicator VARCHAR,
                comment TEXT,
                message TEXT NOT NULL,
                raw_submission TEXT NOT NULL,
                timestamp_submission TIMESTAMP DEFAULT now() NOT NULL,
                approperiate VARCHAR(10),
                CONSTRAINT event_pk PRIMARY KEY (uid)
);
COMMENT ON COLUMN public.event.priority IS 'Called PRI in syslog lingo - the priorit of the message (136 means audit message info level vs. 131 audit message of critical level)';
COMMENT ON COLUMN public.event.timestamp_message IS 'timestamp as specified in the message by the *originating* application';
COMMENT ON COLUMN public.event.hostname IS 'Hostname (or IP address) as per the message';
COMMENT ON COLUMN public.event.app_name IS 'Identification for kind of message. IHE has specified "IHE+SOLE" for SOLE event reports. The event reports from other sources and other profiles will also be retrieved, e.g., "ATNA+3881", if this is not used for filtering.';
COMMENT ON COLUMN public.event.process_id IS 'Typically a process ID for a syslog process. Used to identify logging discontinuities.';
COMMENT ON COLUMN public.event.message_id IS 'SOLE has specified that this will be the SOLE EventTypeCodes, e.g., "RID4585".';
COMMENT ON COLUMN public.event.message IS 'Stores the raw value of the "message" attribute event, which may be a one-liner brief message or a multi-line XML (for ATNA messages) for example';
COMMENT ON COLUMN public.event.raw_submission IS 'Stores the raw submission of the event - useful to dump the events and re-create the DB if need be.';
COMMENT ON COLUMN public.event.timestamp_submission IS 'timestamp of when the message was received by the repository';


ALTER SEQUENCE public.event_uid_seq OWNED BY public.event.uid;

CREATE INDEX event_priority_idx
 ON public.event
 ( priority );

CREATE INDEX event_timestamp_message_idx
 ON public.event
 ( timestamp_message );

CREATE INDEX event_hostname_idx
 ON public.event
 ( hostname );

CREATE INDEX event_app_name_idx
 ON public.event
 ( app_name );

CREATE INDEX event_message_id_idx
 ON public.event
 ( message_id );

CREATE TABLE public.event_patient_map (
                event_uid BIGINT NOT NULL,
                patient_uid BIGINT NOT NULL,
                CONSTRAINT event_patient_map_pk PRIMARY KEY (event_uid, patient_uid)
);


CREATE TABLE public.event_location_map (
                event_uid BIGINT NOT NULL,
                location_uid BIGINT NOT NULL,
                CONSTRAINT event_location_map_pk PRIMARY KEY (event_uid, location_uid)
);


CREATE TABLE public.event_resource_map (
                event_uid BIGINT NOT NULL,
                resource_uid BIGINT NOT NULL,
                CONSTRAINT event_resource_map_pk PRIMARY KEY (event_uid, resource_uid)
);


CREATE TABLE public.event_object_map (
                event_uid BIGINT NOT NULL,
                object_uid BIGINT NOT NULL,
                CONSTRAINT event_object_map_pk PRIMARY KEY (event_uid, object_uid)
);


CREATE TABLE public.event_person_map (
                event_uid BIGINT NOT NULL,
                person_uid BIGINT NOT NULL,
                CONSTRAINT event_person_map_pk PRIMARY KEY (event_uid, person_uid)
);


CREATE TABLE public.event_machine_map (
                event_uid BIGINT NOT NULL,
                machine_uid BIGINT NOT NULL,
                CONSTRAINT event_machine_map_pk PRIMARY KEY (event_uid, machine_uid)
);


CREATE TABLE public.event_type_code_map (
                event_uid BIGINT NOT NULL,
                type_code_uid BIGINT NOT NULL,
                CONSTRAINT event_type_code_map_pk PRIMARY KEY (event_uid, type_code_uid)
);


ALTER TABLE public.event_patient_map ADD CONSTRAINT patient_event_a_map_5_fk
FOREIGN KEY (patient_uid)
REFERENCES public.patient (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_location_map ADD CONSTRAINT location_event_a_map_4_fk
FOREIGN KEY (location_uid)
REFERENCES public.location (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_resource_map ADD CONSTRAINT resource_event_a_map_3_fk
FOREIGN KEY (resource_uid)
REFERENCES public.resource (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_person_map ADD CONSTRAINT person_event_a_map_1_fk
FOREIGN KEY (person_uid)
REFERENCES public.person (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_object_map ADD CONSTRAINT object_event_a_map_2_fk
FOREIGN KEY (object_uid)
REFERENCES public.object (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_machine_map ADD CONSTRAINT machine_event_machine_map_fk
FOREIGN KEY (machine_uid)
REFERENCES public.machine (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event ADD CONSTRAINT audit_source_event_fk
FOREIGN KEY (audit_source_uid)
REFERENCES public.audit_source (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_type_code_map ADD CONSTRAINT type_code_event_type_code_map_fk
FOREIGN KEY (type_code_uid)
REFERENCES public.type_code (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_type_code_map ADD CONSTRAINT event_event_type_code_map_fk
FOREIGN KEY (event_uid)
REFERENCES public.event (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_machine_map ADD CONSTRAINT event_event_a_map_fk
FOREIGN KEY (event_uid)
REFERENCES public.event (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_person_map ADD CONSTRAINT event_event_a_map_1_fk
FOREIGN KEY (event_uid)
REFERENCES public.event (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_object_map ADD CONSTRAINT event_event_a_map_2_fk
FOREIGN KEY (event_uid)
REFERENCES public.event (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_resource_map ADD CONSTRAINT event_event_a_map_3_fk
FOREIGN KEY (event_uid)
REFERENCES public.event (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_location_map ADD CONSTRAINT event_event_a_map_4_fk
FOREIGN KEY (event_uid)
REFERENCES public.event (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;

ALTER TABLE public.event_patient_map ADD CONSTRAINT event_event_a_map_5_fk
FOREIGN KEY (event_uid)
REFERENCES public.event (uid)
ON DELETE NO ACTION
ON UPDATE NO ACTION
NOT DEFERRABLE;