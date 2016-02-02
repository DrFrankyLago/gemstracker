
CREATE TABLE gems__agenda_activities (
        gaa_id_activity     bigint not null ,
        gaa_name            varchar(250) ,

        gaa_id_organization bigint,

        gaa_name_for_resp   varchar(50) ,
        gaa_match_to        varchar(250) ,
        gaa_code            varchar(40) ,

        gaa_active          TINYINT(1) not null default 1,

        gaa_changed         TEXT not null default current_timestamp,
        gaa_changed_by      bigint not null,
        gaa_created         TEXT not null default '0000-00-00 00:00:00',
        gaa_created_by      bigint not null,

        PRIMARY KEY (gaa_id_activity)
    )
    ;


CREATE TABLE gems__agenda_procedures (
        gapr_id_procedure    bigint not null ,
        gapr_name            varchar(250) ,

        gapr_id_organization bigint,

        gapr_name_for_resp   varchar(50) ,
        gapr_match_to        varchar(250) ,
        gapr_code            varchar(40) ,

        gapr_active          TINYINT(1) not null default 1,

        gapr_changed         TEXT not null default current_timestamp,
        gapr_changed_by      bigint not null,
        gapr_created         TEXT not null default '0000-00-00 00:00:00',
        gapr_created_by      bigint not null,

        PRIMARY KEY (gapr_id_procedure)
    )
    ;


CREATE TABLE gems__agenda_staff (
        gas_id_staff        bigint not null ,
        gas_name            varchar(250) ,
        gas_function        varchar(50) ,

        gas_id_organization bigint not null,
        gas_id_user         bigint,

        gas_match_to        varchar(250) ,

        gas_active          TINYINT(1) not null default 1,

        gas_changed         TEXT not null default current_timestamp,
        gas_changed_by      bigint not null,
        gas_created         TEXT not null default '0000-00-00 00:00:00',
        gas_created_by      bigint not null,

        PRIMARY KEY (gas_id_staff)
    )
    ;


CREATE TABLE gems__appointments (
        gap_id_appointment      bigint not null ,
        gap_id_user             bigint not null,
        gap_id_organization     bigint not null,

        gap_source              varchar(20) not null default 'manual',
        gap_id_in_source        varchar(40),
        gap_manual_edit         TINYINT(1) not null default 0,

        gap_code                varchar(1) not null default 'A',
        -- one off A => Ambulatory, E => Emergency, F => Field, H => Home, I => Inpatient, S => Short stay, V => Virtual
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        -- moodCode http://wiki.ihe.net/index.php?title=1.3.6.1.4.1.19376.1.5.3.1.4.14
        -- one of  PRMS Scheduled, ARQ requested but no TEXT, EVN has occurred
        gap_status              varchar(2) not null default 'AC',
        -- one off AB => Aborted, AC => active, CA => Cancelled, CO => completed
        -- see http://wiki.hl7.org/index.php?title=PA_Patient_Encounter

        gap_admission_time      TEXT not null,
        gap_discharge_time      TEXT,

        gap_id_attended_by      bigint,
        gap_id_referred_by      bigint,
        gap_id_activity         bigint,
        gap_id_procedure        bigint,
        gap_id_location         bigint,

        gap_subject             varchar(250),
        gap_comment             TEXT,

        gap_changed             TEXT not null default current_timestamp,
        gap_changed_by          bigint not null,
        gap_created             TEXT not null,
        gap_created_by          bigint not null,

        PRIMARY KEY (gap_id_appointment),
        UNIQUE (gap_id_in_source, gap_id_organization, gap_source)
    )
    ;
CREATE TABLE "gems__chart_config" (
  "gcc_id" bigint(20) NOT NULL ,
  "gcc_tid" bigint(20),
  "gcc_rid" bigint(20),
  "gcc_sid" bigint(20),
  "gcc_code" varchar(16),
  "gcc_config" text,
  "gcc_description" varchar(64),

  "gcc_changed"          TEXT not null default current_timestamp,
  "gcc_changed_by"       bigint not null,
  "gcc_created"          TEXT not null,
  "gcc_created_by"       bigint not null,

  PRIMARY KEY ("gcc_id")

) ;
CREATE TABLE gems__comm_jobs (
        gcj_id_job bigint not null ,

        gcj_id_message bigint not null,

        gcj_id_user_as bigint not null,

        gcj_active TINYINT(1) not null default 1,

        -- O Use organization from address
        -- S Use site from address
        -- U Use gcj_id_user_as from address
        -- F Fixed gcj_from_fixed
        gcj_from_method varchar(1) not null,
        gcj_from_fixed varchar(254),

        -- M => multiple per respondent, one for each token
        -- O => One per respondent, mark all tokens as send
        -- A => Send only one token, do not mark
        gcj_process_method varchar(1) not null,

        -- N => notmailed
        -- R => reminder
        gcj_filter_mode          VARCHAR(1) not null,
        gcj_filter_days_between  INT NOT NULL DEFAULT 7,
        gcj_filter_max_reminders INT NOT NULL DEFAULT 3,

        -- Optional filters
        gcj_id_organization bigint,
        gcj_id_track        int,
        gcj_round_description varchar(100),
        gcj_id_survey       int,

        gcj_changed TEXT not null default current_timestamp,
        gcj_changed_by bigint not null,
        gcj_created TEXT not null default '0000-00-00 00:00:00',
        gcj_created_by bigint not null,

        PRIMARY KEY (gcj_id_job)
   )
   ;

CREATE TABLE gems__comm_templates (
      gct_id_template bigint not null ,

      gct_name        varchar(100) not null,
      gct_target      varchar(32) not null,
      gct_code        varchar(64),

      gct_changed     TEXT not null default current_timestamp,
      gct_changed_by  bigint not null,
      gct_created     TEXT not null default '0000-00-00 00:00:00',
      gct_created_by  bigint not null,

      PRIMARY KEY (gct_id_template),
      UNIQUE (gct_name)
   )
   ;

INSERT INTO gems__comm_templates (gct_id_template, gct_name, gct_target, gct_code, gct_changed, gct_changed_by, gct_created, gct_created_by)
    VALUES
    (15, 'Questions for your treatement at {organization}', 'token',,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (16, 'Reminder: your treatement at {organization}', 'token',,CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (17, 'Global Password reset', 'staffPassword', 'passwordReset', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    (18, 'Global Account created', 'staffPassword', 'accountCreate', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__comm_template_translations (
      gctt_id_template  bigint not null,
      gctt_lang      varchar(2) not null,
      gctt_subject      varchar(100),
      gctt_body         text,


      PRIMARY KEY (gctt_id_template,gctt_lang)
   )
   ;

INSERT INTO gems__comm_template_translations (gctt_id_template, gctt_lang, gctt_subject, gctt_body)
    VALUES
    (15, 'en', 'Questions for your treatement at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (16, 'en', 'Reminder: your treatement at {organization}', 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}'),
    (17, 'en', 'Password re,
    (17, 'nl', 'Wachtwoord opnieuw instellen aangevraagd', 'Om een nieuw wachtwoord in te stellen voor de [b]{organization}[/b] site [b]{project}[/b], klik op deze link:\n{reset_url}'),
    (18, 'en', 'New account created', 'A new account has been created for the [b]{organization}[/b] site [b]{project}[/b].
To ,
    (18, 'nl', 'Nieuw account aangemaakt', 'Een nieuw account is aangemaakt voor de [b]{organization}[/b] site [b]{project}[/b].
Om uw wachtwoord te kiezen en uw account te activeren, klik op deze link:\n{reset_url}');
CREATE TABLE gems__consents (
      gco_description varchar(20) not null,
      gco_order smallint not null default 10,
      gco_code varchar(20) not null default 'do not use',

      gco_changed TEXT not null default current_timestamp,
      gco_changed_by bigint not null,
      gco_created TEXT not null,
      gco_created_by bigint not null,

      PRIMARY KEY (gco_description)
    )
    ;


INSERT INTO gems__consents 
    (gco_description, gco_order, gco_code, gco_changed, gco_changed_by, gco_created, gco_created_by) 
    VALUES
    ('Yes', 10, 'consent given', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('No', 20, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('Unknown', 30, 'do not use', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__groups (
      ggp_id_group bigint not null ,
      ggp_name varchar(30) not null,
      ggp_description varchar(50) not null,

      ggp_role varchar(150) not null default 'respondent',
      -- The ggp_role value(s) determines someones roles as not null default 1,
      ggp_staff_members TINYINT(1) not null default 0,
      ggp_respondent_members TINYINT(1) not null default 1,
      ggp_allowed_ip_ranges text,

      ggp_changed TEXT not null default current_timestamp,
      ggp_changed_by bigint not null,
      ggp_created TEXT not null,
      ggp_created_by bigint not null,

      PRIMARY KEY(ggp_id_group)
   )
   ;

-- Default group
INSERT ignore INTO gems__groups
   (ggp_id_group, ggp_name, ggp_description, ggp_role, ggp_group_active, ggp_staff_members, ggp_respondent_members, ggp_changed_by, ggp_created, ggp_created_by)
   VALUES
   (800, 'Super Administrators', 'Super administrators with access to the whole site', 'super', 1, 1, 0, 0, current_timestamp, 0),
   (801, 'Local Admins', 'Local Administrators', 'admin', 1, 1, 0, 0, current_timestamp, 0),
   (802, 'Staff', 'Health care staff', 'staff', 1, 1, 0, 0, current_timestamp, 0),
   (803, 'Respondents', 'Respondents', 'respondent', 1, 0, 1, 0, current_timestamp, 0);

CREATE TABLE gems__locations (
        glo_id_location     bigint not null ,
        glo_name            varchar(40) ,

        -- Yes, quick and dirty, will correct later (probably)
        glo_organizations     varchar(250) ,

        glo_match_to        varchar(250) ,
        glo_code            varchar(40) ,

        glo_url             varchar(250) ,
        glo_url_route       varchar(250) ,

        glo_address_1       varchar(80) ,
        glo_address_2       varchar(80) ,
        glo_zipcode         varchar(10) ,
        glo_city            varchar(40) ,
        -- glo_region          varchar(40) ,
        glo_iso_country     char(2) not null default 'NL',
        glo_phone_1         varchar(25) ,
        -- glo_phone_2         varchar(25) ,
        -- glo_phone_3         varchar(25) ,
        -- glo_phone_4         varchar(25) ,

        glo_active          TINYINT(1) not null default 1,

        glo_changed         TEXT not null default current_timestamp,
        glo_changed_by      bigint not null,
        glo_created         TEXT not null default '0000-00-00 00:00:00',
        glo_created_by      bigint not null,

        PRIMARY KEY (glo_id_location)
    )
    ;

CREATE TABLE gems__log_respondent_communications (
        grco_id_action    bigint not null ,

        grco_id_to        bigint not null,
        grco_id_by        bigint default 0,
        grco_organization bigint not null,

        grco_id_token     varchar(9),

        grco_method       varchar(12) not null,
        grco_topic        varchar(120) not null,
        grco_address      varchar(120),
        grco_sender       varchar(120),
        grco_comments     varchar(120),

        grco_id_message   bigint,

        grco_changed      TEXT not null default current_timestamp,
        grco_changed_by   bigint not null,
        grco_created      TEXT not null,
        grco_created_by   bigint not null,

        PRIMARY KEY (grco_id_action)
    )
    ;


-- depreciated, moved to gems__comm_jobs

CREATE TABLE gems__mail_jobs (
        gmj_id_job bigint not null ,

        gmj_id_message bigint not null,

        gmj_id_user_as bigint not null,

        gmj_active TINYINT(1) not null default 1,

        -- O Use organization from address
        -- S Use site from address
        -- U Use gmj_id_user_as from address
        -- F Fixed gmj_from_fixed
        gmj_from_method varchar(1) not null,
        gmj_from_fixed varchar(254),

        -- M => multiple per respondent, one for each token
        -- O => One per respondent, mark all tokens as send
        -- A => Send only one token, do not mark
        gmj_process_method varchar(1) not null,

        -- N => notmailed
        -- R => reminder
        gmj_filter_mode          VARCHAR(1) not null,
        gmj_filter_days_between  INT NOT NULL DEFAULT 7,
        gmj_filter_max_reminders INT NOT NULL DEFAULT 3,

        -- Optional filters
        gmj_id_organization bigint,
        gmj_id_track        int,
        gmj_id_survey       int,

        gmj_changed TEXT not null default current_timestamp,
        gmj_changed_by bigint not null,
        gmj_created TEXT not null default '0000-00-00 00:00:00',
        gmj_created_by bigint not null,

        PRIMARY KEY (gmj_id_job)
   )
   ;

CREATE TABLE gems__mail_servers (
      gms_from       varchar(100) not null,

      gms_server     varchar(100) not null,
      gms_port       smallint not null default 25,
      gms_ssl        tinyint not null default 0,
      gms_user       varchar(100),
      gms_password   varchar(100),

      gms_changed    TEXT not null default current_timestamp,
      gms_changed_by bigint not null,
      gms_created    TEXT not null default '0000-00-00 00:00:00',
      gms_created_by bigint not null,

      PRIMARY KEY (gms_from)
   )
   ;


-- depreciated, moved to gems__comm_templates and gems__comm_template_translations

CREATE TABLE gems__mail_templates (
      gmt_id_message bigint not null ,

      gmt_subject    varchar(100) not null,
      gmt_body       text not null,

      -- Yes, quick and dirty, will correct later (probably)
      gmt_organizations varchar(250) ,

      gmt_changed TEXT not null default current_timestamp,
      gmt_changed_by bigint not null,
      gmt_created TEXT not null default '0000-00-00 00:00:00',
      gmt_created_by bigint not null,

      PRIMARY KEY (gmt_id_message),
      UNIQUE (gmt_subject)
   )
   ;

INSERT INTO gems__mail_templates (gmt_subject, gmt_body, gmt_changed, gmt_changed_by, gmt_created, gmt_created_by)
    VALUES
    ('Questions for your treatement at {organization}', 'Dear {greeting},

Recently you visited [b]{organization}[/b] for treatment. For your proper treatment you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('Reminder: your treatement at {organization}', 'Dear {greeting},

We remind you that for your proper treatment at [b]{organization}[/b] you are required to answer some questions.

Click on [url={token_url}]this link[/url] to start or go to [url]{site_ask_url}[/url] and enter your token "{token}".

{organization_signature}', CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);
CREATE TABLE "gems__openrosaforms" (
  "gof_id" bigint(20) NOT NULL ,
  "gof_form_id" varchar(249) NOT NULL,
  "gof_form_version" varchar(249) NOT NULL,
  "gof_form_active" int(1) NOT NULL default '1',
  "gof_form_title" text NOT NULL,
  "gof_form_xml" varchar(64) NOT NULL,
  "gof_changed" TEXT NOT NULL default CURRENT_TIMESTAMP,
  "gof_changed_by" bigint(20) NOT NULL,
  "gof_created" TEXT NOT NULL default '0000-00-00 00:00:00',
  "gof_createf_by" bigint(20) NOT NULL,
  PRIMARY KEY  ("gof_id")
) ;
CREATE TABLE gems__organizations (
        gor_id_organization  bigint not null ,

        gor_name             varchar(50)   not null,
        gor_code             varchar(20),
        gor_user_class       varchar(30)   not null default 'StaffUser',
        gor_location         varchar(50),
        gor_url              varchar(127),
        gor_url_base         varchar(1270),
        gor_task             varchar(50),

        gor_provider_id      varchar(10),

        -- A comma separated list of organization numbers that can look at respondents in this organization
        gor_accessible_by    text,

        gor_contact_name     varchar(50),
        gor_contact_email    varchar(127),
        gor_welcome          text,
        gor_signature        text,

        gor_style            varchar(15)  not null default 'gems',
        gor_iso_lang         char(2) not null default 'en',

        gor_has_login               TINYINT(1) not null default 1,
        gor_has_respondents         TINYINT(1) not null default 0,
        gor_add_respondents         TINYINT(1) not null default 1,
        gor_respondent_group        bigint,
        gor_create_account_template bigint,
        gor_reset_pass_template     bigint,
        gor_allowed_ip_ranges       text,
        gor_active                  TINYINT(1) not null default 1,

        gor_changed          TEXT not null default current_timestamp,
        gor_changed_by       bigint not null,
        gor_created          TEXT not null,
        gor_created_by       bigint not null,

        PRIMARY KEY(gor_id_organization)
    )
    ;

INSERT ignore INTO gems__organizations (gor_id_organization, gor_name, gor_changed, gor_changed_by, gor_created, gor_created_by)
    VALUES
    (70, 'New organization', CURRENT_TIMESTAMP, 0, CURRENT_TIMESTAMP, 0);

CREATE TABLE gems__patches (
      gpa_id_patch  int not null ,

      gpa_level     int not null default 0,
      gpa_location  varchar(100) not null,
      gpa_name      varchar(30) not null,
      gpa_order     int not null default 0,

      gpa_sql       text not null,

      gpa_executed  TINYINT(1) not null default 0, 
      gpa_completed TINYINT(1) not null default 0, 

      gpa_result    varchar(255),

      gpa_changed  TEXT not null default current_timestamp,
      gpa_created  TEXT,
      
      PRIMARY KEY (gpa_id_patch),
      UNIQUE (gpa_level, gpa_location, gpa_name, gpa_order)
   )
   ;


CREATE TABLE gems__patch_levels (
      gpl_level   int not null unique,

      gpl_created TEXT not null default current_timestamp,

      PRIMARY KEY (gpl_level)
   )
   ;

INSERT INTO gems__patch_levels (gpl_level, gpl_created)
   VALUES
   (56, CURRENT_TIMESTAMP);

CREATE TABLE gems__radius_config (
        grcfg_id              bigint(11) NOT NULL ,
        grcfg_id_organization bigint(11) NOT NULL,
        grcfg_ip              varchar(39),
        grcfg_port            int(5),
        grcfg_secret          varchar(32),

        PRIMARY KEY (grcfg_id)
    )
;
CREATE TABLE gems__reception_codes (
      grc_id_reception_code varchar(20) not null,
      grc_description       varchar(40) not null,

      grc_success           TINYINT(1) not null default 0,

      grc_for_surveys       tinyint not null default 0,
      grc_redo_survey       tinyint not null default 0,
      grc_for_tracks        TINYINT(1) not null default 0,
      grc_for_respondents   TINYINT(1) not null default 0,
      grc_overwrite_answers TINYINT(1) not null default 0,
      grc_active            TINYINT(1) not null default 1,

      grc_changed    TEXT not null default current_timestamp,
      grc_changed_by bigint not null,
      grc_created    TEXT not null,
      grc_created_by bigint not null,

      PRIMARY KEY (grc_id_reception_code)
   )
   ;

INSERT INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
      grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_overwrite_answers, grc_active,
      grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
    ('OK', '', 1, 1, 0, 1, 1, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('redo', 'Redo survey', 0, 1, 2, 0, 0, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('refused', 'Survey refused', 0, 1, 0, 0, 0, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('retract', 'Consent retracted', 0, 0, 0, 1, 1, 1, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('skip', 'Skipped by calculation', 0, 1, 0, 0, 0, 1, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1),
    ('stop', 'Stopped participating', 0, 2, 0, 1, 1, 0, 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__respondent2org (
        gr2o_patient_nr varchar(15) not null,
        gr2o_id_organization bigint not null,

        gr2o_id_user bigint not null,

        -- gr2o_id_physician bigint
        --,

        -- gr2o_treatment varchar(200),
        gr2o_mailable TINYINT(1) not null default 1,
        gr2o_comments text,

        gr2o_consent varchar(20) not null default 'Unknown',
        gr2o_reception_code varchar(20) default 'OK' not null,

        gr2o_opened TEXT not null default current_timestamp,
        gr2o_opened_by bigint not null,
        gr2o_changed TEXT not null,
        gr2o_changed_by bigint not null,
        gr2o_created TEXT not null,
        gr2o_created_by bigint not null,

        PRIMARY KEY (gr2o_patient_nr, gr2o_id_organization),
        UNIQUE (gr2o_id_user, gr2o_id_organization)
    )
    ;


CREATE TABLE gems__respondent2track (
        gr2t_id_respondent_track    bigint not null ,

        gr2t_id_user                bigint not null,
        gr2t_id_track               int not null,

        gr2t_track_info             varchar(250) ,
        gr2t_start_date             TEXT,
        gr2t_end_date               TEXT,
        gr2t_end_date_manual        TINYINT(1) not null default 0,

        gr2t_id_organization        bigint not null,

        gr2t_mailable               TINYINT(1) not null default 1,
        gr2t_active                 TINYINT(1) not null default 1,
        gr2t_count                  int not null default 0,
        gr2t_completed              int not null default 0,

        gr2t_reception_code         varchar(20) default 'OK' not null,
        gr2t_comment                varchar(250),

        gr2t_changed                TEXT not null default current_timestamp,
        gr2t_changed_by             bigint not null,
        gr2t_created                TEXT not null,
        gr2t_created_by             bigint not null,

        PRIMARY KEY (gr2t_id_respondent_track)
    )
    ;

CREATE TABLE gems__respondent2track2appointment (
        gr2t2a_id_respondent_track  bigint not null,
        gr2t2a_id_app_field         bigint not null,
        gr2t2a_id_appointment       bigint,

        gr2t2a_changed              TEXT not null default current_timestamp,
        gr2t2a_changed_by           bigint not null,
        gr2t2a_created              TEXT not null,
        gr2t2a_created_by           bigint not null,

        PRIMARY KEY(gr2t2a_id_respondent_track, gr2t2a_id_app_field)
    )
    ;


CREATE TABLE gems__respondent2track2field (
        gr2t2f_id_respondent_track bigint not null,
        gr2t2f_id_field bigint not null,

        gr2t2f_value text,

        gr2t2f_changed TEXT not null default current_timestamp,
        gr2t2f_changed_by bigint not null,
        gr2t2f_created TEXT not null,
        gr2t2f_created_by bigint not null,

        PRIMARY KEY(gr2t2f_id_respondent_track,gr2t2f_id_field)
    )
    ;


CREATE TABLE gems__respondents (
      grs_id_user                bigint not null,

      grs_ssn                    varchar(128) unique,

      grs_iso_lang               char(2) not null default 'nl',

      grs_email                  varchar(100),

      -- grs_initials_name          varchar(30) ,
      grs_first_name             varchar(30) ,
      -- grs_surname_prefix         varchar(10) ,
      grs_last_name              varchar(50) ,
      -- grs_partner_surname_prefix varchar(10) ,
      -- grs_partner_last_name      varchar(50) ,
      grs_gender                 char(1) not null default 'U',
      grs_birthday               TEXT,

      grs_address_1              varchar(80) ,
      grs_address_2              varchar(80) ,
      grs_zipcode                varchar(10) ,
      grs_city                   varchar(40) ,
      -- grs_region                 varchar(40) ,
      grs_iso_country            char(2) not null default 'NL',
      grs_phone_1                varchar(25) ,
      grs_phone_2                varchar(25) ,
      -- grs_phone_3                varchar(25) ,
      -- grs_phone_4                varchar(25) ,

      grs_changed                TEXT not null default current_timestamp,
      grs_changed_by             bigint not null,
      grs_created                TEXT not null,
      grs_created_by             bigint not null,

      PRIMARY KEY(grs_id_user)
   )
   ;


CREATE TABLE gems__roles (
      grl_id_role bigint not null ,
      grl_name varchar(30) not null,
      grl_description varchar(50) not null,

      grl_parents text,
      -- The grl_parents is a comma-separated list of parents for this role

      grl_privileges text,
      -- The grl_privilege is a comma-separated list of privileges for this role

      grl_changed TEXT not null default current_timestamp,
      grl_changed_by bigint not null,
      grl_created TEXT not null,
      grl_created_by bigint not null,

      PRIMARY KEY(grl_id_role)
   )
   ;

-- default roles/privileges

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (800, 'nologin', 'nologin',,
    'pr.contact.bugs,pr.contact.support,pr.cron.job,pr.nologin',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (801, 'guest', 'guest',,
    'pr.ask,pr.contact.bugs,pr.contact.gems,pr.contact.support,pr.cron.job,pr.islogin,pr.respondent',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (802, 'respondent','respondent', '801',
    '',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (803, 'security', 'security', '801',
    '',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (804, 'staff', 'staff', '801',
    'pr.option.edit,pr.option.password,
    ,pr.plan,pr.plan.compliance,pr.plan.overview,pr.plan.summary,pr.plan.token,
    ,pr.project,pr.project.questions,
    ,pr.respondent.create,pr.respondent.edit,pr.respondent.reportdeath,pr.respondent.who,
    ,pr.survey,pr.survey.create,
    ,pr.token,pr.token.answers,pr.token.delete,pr.token.edit,pr.token.mail,pr.token.print,
    ,pr.track,pr.track.create,pr.track.delete,pr.track.edit',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (805, 'physician', 'physician', '804',
    '',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (806, 'researcher', 'researcher', '801',
    'pr.project-information.changelog,pr.contact,pr.export,pr.plan.token,pr.plan.respondent,pr.plan.overview,
    ,pr.option.password,pr.option.edit,pr.organization-switch,pr.islogin',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (807, 'admin', 'admin', '801,803,804,805,806',
    'pr.comm.job,
    ,pr.comm.template,pr.comm.template.create,pr.comm.template.delete,pr.comm.template.edit,pr.comm.template.log,
    ,pr.consent,pr.consent.create,pr.consent.edit,
    ,pr.group,
    ,pr.organization,pr.organization-switch,
    ,pr.plan.compliance.excel,pr.plan.overview.excel,
    ,pr.plan.respondent,pr.plan.respondent.excel,pr.plan.summary.excel,pr.plan.token.excel,
    ,pr.project-information,
    ,pr.reception,pr.reception.create,pr.reception.edit,
    ,pr.respondent.choose-org,pr.respondent.delete,pr.respondent.result,
    ,pr.role,
    ,pr.staff,pr.staff.create,pr.staff.delete,pr.staff.edit,pr.staff.see.all,
    ,pr.source,
    ,pr.survey-maintenance,
    ,pr.token.mail.freetext,
    ,pr.track-maintenance,pr.track-maintenance.trackperorg',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

INSERT ignore INTO gems__roles (grl_id_role, grl_name, grl_description, grl_parents,
        grl_privileges,
        grl_changed, grl_changed_by, grl_created, grl_created_by)
    VALUES
    (808, 'super', 'super', '801,803,804,805,806',
    'pr.agenda-activity,pr.agenda-activity.create,pr.agenda-activity.delete,pr.agenda-activity.edit,
    ,pr.agenda-procedure,pr.agenda-procedure.create,pr.agenda-procedure.delete,pr.agenda-procedure.edit,
    ,pr.agenda-staff,pr.agenda-staff.create,pr.agenda-staff.delete,pr.agenda-staff.edit,
    ,pr.comm.job.create,pr.comm.job.edit,pr.comm.job.delete,,
    ,pr.comm.server,pr.comm.server.create,pr.comm.server.delete,pr.comm.server.edit,
    ,pr.consent.delete,
    ,pr.database,pr.database.create,pr.database.delete,pr.database.edit,pr.database.execute,pr.database.patches,
    ,pr.group.create,pr.group.edit,
    ,pr.locations,pr.locations.create,pr.locations.delete,pr.locations.edit,
    ,pr.maintenance,pr.maintenance.clean-cache,pr.maintenance.maintenance-mode,
    ,pr.organization.create,pr.organization.edit,
    ,pr.plan.mail-as-application,pr.reception.delete,
    ,pr.respondent.multiorg,
    ,pr.role.create,pr.role.edit,
    ,pr.source.check-attributes,pr.source.check-attributes-all,pr.source.create,pr.source.edit,pr.source.synchronize,
    ,pr.source.synchronize-all,
    ,pr.staff.edit.all,
    ,pr.survey-maintenance.edit,
    ,pr.track-maintenance.create,pr.track-maintenance.edit',
    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

CREATE TABLE gems__rounds (
        gro_id_round           bigint not null ,

        gro_id_track           bigint not null,
        gro_id_order           int not null default 10,

        gro_id_survey          bigint not null,

        -- Survey_name is a temp copy from __surveys, needed by me to keep an overview as
        -- long as no track editor exists.
        gro_survey_name        varchar(100) not null,

        gro_round_description  varchar(100),
        gro_icon_file          varchar(100),
        gro_changed_event      varchar(128),
        gro_display_event      varchar(128),

        gro_valid_after_id     bigint,
        gro_valid_after_source varchar(12) not null default 'tok',
        gro_valid_after_field  varchar(64) not null
                               default 'gto_valid_from',
        gro_valid_after_unit   char(1) not null default 'M',
        gro_valid_after_length int not null default 0,

        gro_valid_for_id       bigint,
        gro_valid_for_source   varchar(12) not null default 'nul',
        gro_valid_for_field    varchar(64),
        gro_valid_for_unit     char(1) not null default 'M',
        gro_valid_for_length   int not null default 0,

        gro_active             TINYINT(1) not null default 1,

        gro_changed            TEXT not null default current_timestamp,
        gro_changed_by         bigint not null,
        gro_created            TEXT not null,
        gro_created_by         bigint not null,

        PRIMARY KEY (gro_id_round)
    )
    ;


CREATE TABLE "gems__sources" (
  "gso_id_source" int(10) NOT NULL ,
  "gso_source_name" varchar(40) NOT NULL,

  "gso_ls_url" varchar(255) NOT NULL,
  "gso_ls_class" varchar(60) NOT NULL default 'Gems_Source_LimeSurvey1m9Database',
  "gso_ls_adapter" varchar(20),
  "gso_ls_dbhost" varchar(127),
  "gso_ls_database" varchar(127),
  "gso_ls_table_prefix" varchar(127),
  "gso_ls_username" varchar(64),
  "gso_ls_password" varchar(255),
  "gso_ls_charset" varchar(8),

  "gso_active" tinyint(1) NOT NULL default '1',

  "gso_status" varchar(20),
  "gso_last_synch" TEXT,

  "gso_changed" TEXT NOT NULL default CURRENT_TIMESTAMP,
  "gso_changed_by" bigint(20) NOT NULL,
  "gso_created" TEXT NOT NULL default '0000-00-00 00:00:00',
  "gso_created_by" bigint(20) NOT NULL,

  PRIMARY KEY  ("gso_id_source"),
  UNIQUE ("gso_source_name"),
  UNIQUE ("gso_ls_url")
)
;
-- Table containing the project staff
--
CREATE TABLE gems__staff (
        gsf_id_user          bigint not null,

        gsf_login            varchar(20) not null,
        gsf_id_organization  bigint not null,

        gsf_active           TINYINT(1) default 1,

        -- depreciated
        gsf_password         varchar(32),
    	gsf_failed_logins    int(11) default 0,
        gsf_last_failed      TEXT,
        -- end depreciated


        gsf_id_primary_group bigint,
        gsf_iso_lang         char(2) not null default 'en',
        gsf_logout_on_survey TINYINT(1) not null default 0,

        gsf_email            varchar(100) ,

        gsf_first_name       varchar(30) ,
        gsf_surname_prefix   varchar(10) ,
        gsf_last_name        varchar(30) ,
        gsf_gender           char(1) not null default 'U',
        -- gsf_birthday         TEXT,
        -- gsf_function         varchar(40) ,

        -- gsf_address_1        varchar(80) ,
        -- gsf_address_2        varchar(80) ,
        -- gsf_zipcode          varchar(10) ,
        -- gsf_city             varchar(40) ,
        -- gsf_region           varchar(40) ,
        -- gsf_iso_country      char(2) --,
        gsf_phone_1          varchar(25) ,
        -- gsf_phone_2          varchar(25) ,
        -- gsf_phone_3          varchar(25) ,

        -- depreciated
        gsf_reset_key        varchar(64),
        gsf_reset_req        TEXT,
        -- end depreciated

        gsf_changed          TEXT not null default current_timestamp,
        gsf_changed_by       bigint not null,
        gsf_created          TEXT not null,
        gsf_created_by       bigint not null,

        PRIMARY KEY (gsf_id_user),
        UNIQUE (gsf_login, gsf_id_organization),
        UNIQUE (gsf_reset_key)
    )
    ;


CREATE TABLE gems__staff2groups (
        gs2g_id_user bigint not null,
        gs2g_id_group bigint not null,

        gs2g_active TINYINT(1) not null default 1,

        gs2g_changed TEXT not null default current_timestamp,
        gs2g_changed_by bigint not null,
        gs2g_created TEXT not null,
        gs2g_created_by bigint not null,

        PRIMARY KEY (gs2g_id_user, gs2g_id_group)
    )
    ;



CREATE TABLE gems__surveys (
        gsu_id_survey int not null ,
        gsu_survey_name varchar(100) not null,
        gsu_survey_description varchar(100) ,

        gsu_surveyor_id int(11),
        gsu_surveyor_active TINYINT(1) not null default 1,

        -- depreciated
        gsu_survey_table varchar(64) ,
        gsu_token_table varchar(64) ,
        -- end depreciated

        gsu_survey_pdf            varchar(128) ,
        gsu_beforeanswering_event varchar(128) ,
        gsu_completed_event       varchar(128) ,
        gsu_display_event         varchar(128) ,

        gsu_id_source int not null,
        gsu_active TINYINT(1) not null default 0,
        gsu_status varchar(127) ,

        -- depreciated
        -- gsu_staff TINYINT(1) not null default 0,
        -- end depreciated

        gsu_id_primary_group bigint,

        -- depreciated
        -- gsu_id_user_field varchar(20) ,
        -- gsu_completion_field varchar(20) not null default 'submitdate',
        -- gsu_followup_field varchar(20) not null default 'submitdate',
        -- end depreciated

        gsu_result_field   varchar(20) ,
        gsu_agenda_result  varchar(20) ,
        gsu_duration       varchar(50) ,

        gsu_code           varchar(64),

        gsu_changed TEXT not null default current_timestamp,
        gsu_changed_by bigint not null,
        gsu_created TEXT not null,
        gsu_created_by bigint not null,

        PRIMARY KEY(gsu_id_survey)
    )
    ;


CREATE TABLE gems__survey_questions (
        gsq_id_survey       int not null,
        gsq_name            varchar(100) not null,

        gsq_name_parent     varchar(100) ,
        gsq_order           int not null default 10,
        gsq_type            smallint not null default 1,
        gsq_class           varchar(50) ,
        gsq_group           varchar(100) ,

        gsq_label           varchar(100) ,
        gsq_description     varchar(200) ,

        gsq_changed         TEXT not null default current_timestamp,
        gsq_changed_by      bigint not null,
        gsq_created         TEXT not null,
        gsq_created_by      bigint not null,

        PRIMARY KEY (gsq_id_survey, gsq_name)
    )
    ;

CREATE TABLE gems__survey_question_options (
        gsqo_id_survey      int not null,
        gsqo_name           varchar(100) not null,
        -- Order is key as you never now what is in the key used by the providing system
        gsqo_order          int not null default 0,

        gsqo_key            varchar(100) ,
        gsqo_label          varchar(100) ,

        gsqo_changed        TEXT not null default current_timestamp,
        gsqo_changed_by     bigint not null,
        gsqo_created        TEXT not null,
        gsqo_created_by     bigint not null,

        PRIMARY KEY (gsqo_id_survey, gsqo_name, gsqo_order)
    )
    ;

CREATE TABLE gems__tokens (
        gto_id_token            varchar(9) not null,

        gto_id_respondent_track bigint not null,
        gto_id_round            bigint not null,

        -- non-changing fields calculated from previous two:
        gto_id_respondent       bigint not null,
        gto_id_organization     bigint not null,
        gto_id_track            bigint not null,

        -- values initially filled from gems__rounds, but that may get different values later on
        gto_id_survey           bigint not null,

        -- values initially filled from gems__rounds, but that might get different values later on, but but not now
        gto_round_order         int not null default 10,
        gto_round_description   varchar(100),

        -- real data
        gto_valid_from          TEXT,
        gto_valid_from_manual   TINYINT(1) not null default 0,
        gto_valid_until         TEXT,
        gto_valid_until_manual  TINYINT(1) not null default 0,
        gto_mail_sent_date      TEXT,
        gto_mail_sent_num       int(11) not null default 0,
        -- gto_next_mail_date      TEXT,  -- deprecated

        gto_start_time          TEXT,
        gto_in_source           TINYINT(1) not null default 0,
        gto_by                  bigint(20),

        gto_completion_time     TEXT,
        gto_duration_in_sec     bigint(20),
        -- gto_followup_date       TEXT, -- deprecated
        gto_result              varchar(20) ,

        gto_comment             text,
        gto_reception_code      varchar(20) default 'OK' not null,

        gto_return_url          varchar(250),

        gto_changed             TEXT not null default current_timestamp,
        gto_changed_by          bigint not null,
        gto_created             TEXT not null,
        gto_created_by          bigint not null,

        PRIMARY KEY (gto_id_token)
    )
    ;


CREATE TABLE gems__token_attempts (
        gta_id_attempt bigint not null ,
        gta_id_token varchar(9) not null,
        gta_ip_address varchar(64) not null,
        gta_datetime TEXT not null default current_timestamp,

        PRIMARY KEY (gta_id_attempt)
    )
    ;


CREATE TABLE gems__tracks (
        gtr_id_track          int not null ,
        gtr_track_name        varchar(40) not null unique,

        gtr_track_info        varchar(250) ,
        gtr_code              varchar(64),

        gtr_date_start        TEXT not null,
        gtr_date_until        TEXT,

        gtr_active            TINYINT(1) not null default 0,
        gtr_survey_rounds     int not null default 0,
        gtr_track_type        char(1) not null default 'T',

        -- depreciated
        gtr_track_model varchar(64) not null default 'TrackModel',
        -- end depreciated

        gtr_track_class       varchar(64) not null,
        gtr_calculation_event varchar(128) ,
        gtr_completed_event   varchar(128) ,

        -- Yes, quick and dirty, will correct later (probably)
        gtr_organizations     varchar(250) ,

        gtr_changed           TEXT not null default current_timestamp,
        gtr_changed_by        bigint not null,
        gtr_created           TEXT not null,
        gtr_created_by        bigint not null,

        PRIMARY KEY (gtr_id_track)
    )
    ;


CREATE TABLE gems__track_appointments (
        gtap_id_app_field       bigint not null ,
        gtap_id_track           int not null,

        gtap_id_order           int not null default 10,

        gtap_field_name         varchar(200) not null,
        gtap_field_code         varchar(20),
        gtap_field_description  varchar(200),

        gtap_required           TINYINT(1) not null default false,
        gtap_readonly           TINYINT(1) not null default false,

        gtap_changed            TEXT not null default current_timestamp,
        gtap_changed_by         bigint not null,
        gtap_created            TEXT not null,
        gtap_created_by         bigint not null,

        PRIMARY KEY (gtap_id_app_field)
    )
    ;


CREATE TABLE gems__track_fields (
        gtf_id_field   bigint not null ,
        gtf_id_track   int not null,

        gtf_id_order   int not null default 10,

        gtf_field_name        varchar(200) not null,
        gtf_field_code        varchar(20),
        gtf_field_description varchar(200),

        gtf_field_values      text,

        gtf_field_type        varchar(20) not null,

        gtf_required   TINYINT(1) not null default false,
        gtf_readonly   TINYINT(1) not null default false,

        gtf_changed    TEXT not null default current_timestamp,
        gtf_changed_by bigint not null,
        gtf_created    TEXT not null,
        gtf_created_by bigint not null,

        PRIMARY KEY (gtf_id_field)
    )
    ;


-- Support table for generating unique staff/respondent id's
--
CREATE TABLE gems__user_ids (
        gui_id_user          bigint not null,

        gui_created          TEXT not null,

        PRIMARY KEY (gui_id_user)
    )
    ;

-- Table containing the users that are allowed to login
--
CREATE TABLE gems__user_logins (
        gul_id_user          bigint not null ,

        gul_login            varchar(30) not null,
        gul_id_organization  bigint not null,

        gul_user_class       varchar(30) not null default 'NoLogin',
        gul_can_login        TINYINT(1) not null default 0,

        gul_changed          TEXT not null default current_timestamp,
        gul_changed_by       bigint not null,
        gul_created          TEXT not null,
        gul_created_by       bigint not null,

        PRIMARY KEY (gul_id_user),
        UNIQUE (gul_login, gul_id_organization)
    )
    ;


-- Table for keeping track of failed login attempts
--
CREATE TABLE gems__user_login_attempts (
        gula_login            varchar(30) not null,
        gula_id_organization  bigint not null,

    	gula_failed_logins    int(11) not null default 0,
        gula_last_failed      TEXT,
        gula_block_until      TEXT,

        PRIMARY KEY (gula_login, gula_id_organization)
    )
    ;

-- Table containing the users that are allowed to login
--
CREATE TABLE gems__user_passwords (
        gup_id_user          bigint not null,

        gup_password         varchar(32),
        gup_reset_key        varchar(64),
        gup_reset_requested  TEXT,
        gup_reset_required   TINYINT(1) not null default 0,
        gup_last_pwd_change      TEXT not null default 0,  -- Can only have on current_timestamp so default to 0

        gup_changed          TEXT not null default current_timestamp,
        gup_changed_by       bigint not null,
        gup_created          TEXT not null,
        gup_created_by       bigint not null,

        PRIMARY KEY (gup_id_user),
        UNIQUE (gup_reset_key)
    )
    ;