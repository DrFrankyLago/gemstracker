-- GEMS VERSION: 1
-- PATCH: Test skip earlier patch levels

SELECT NULL;

-- GEMS VERSION: 27
-- PATCH: Use OK reception code

ALTER TABLE `gems__reception_codes`
    ADD
        `grc_success` BOOLEAN NOT NULL DEFAULT '0'
    AFTER `grc_description`;

INSERT IGNORE INTO gems__reception_codes (grc_id_reception_code, grc_description, grc_success,
        grc_for_surveys, grc_redo_survey, grc_for_tracks, grc_for_respondents, grc_active,
        grc_changed, grc_changed_by, grc_created, grc_created_by)
    VALUES
        ('OK', '', 1, 1, 0, 1, 1, 1, 0, CURRENT_TIMESTAMP, 0, CURRENT_TIMESTAMP);

UPDATE gems__tokens SET gto_reception_code = 'OK' WHERE gto_reception_code IS NULL;

ALTER TABLE gems__tokens
    CHANGE COLUMN gto_reception_code
        gto_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'OK';

UPDATE gems__respondent2track SET gr2t_reception_code = 'OK' WHERE gr2t_reception_code IS NULL;

ALTER TABLE gems__respondent2track
    CHANGE COLUMN gr2t_reception_code
        gr2t_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null;

ALTER TABLE gems__respondent2org
    ADD
        gr2o_reception_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' default 'OK' not null
            references gems__reception_codes (grc_id_reception_code)
    AFTER gr2o_consent;

-- PATCH: Longer patch results

ALTER TABLE `gems__patches` CHANGE `gpa_result` `gpa_result` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- PATCH: datestamp to submitdate
ALTER TABLE  `gems__surveys` CHANGE  `gsu_completion_field`  `gsu_completion_field` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'submitdate',
CHANGE  `gsu_followup_field`  `gsu_followup_field` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'submitdate';

UPDATE `gems__surveys` SET `gsu_completion_field` = 'submitdate' WHERE  `gsu_completion_field` = 'datestamp';
UPDATE `gems__surveys` SET `gsu_followup_field` = 'submitdate' WHERE  `gsu_followup_field` = 'datestamp';

--PATCH: Result storage
ALTER TABLE gems__surveys
    ADD gsu_result_field varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gsu_followup_field;

ALTER TABLE gems__tokens
    ADD gto_result varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
    AFTER gto_followup_date;

-- GEMS VERSION: 28
-- PATCH: Track reception / code options

ALTER TABLE `gems__reception_codes`
    ADD `grc_overwrite_answers` boolean not null default '0'
    AFTER `grc_for_respondents`;

-- PATCH: Performance, indexes
ALTER TABLE `gems__tokens` ADD INDEX(`gto_id_survey`);
ALTER TABLE `gems__tokens` ADD INDEX(`gto_id_track`);
ALTER TABLE `gems__tokens` ADD INDEX(`gto_id_round`);
ALTER TABLE `gems__tokens` ADD INDEX(`gto_in_surveyor`);
ALTER TABLE `gems__reception_codes` ADD INDEX (`grc_success`);
ALTER TABLE `gems__tokens` ADD INDEX (`gto_id_respondent_track`);
ALTER TABLE `gems__tracks` ADD INDEX (`gtr_active`);
ALTER TABLE `gems__surveys` ADD INDEX (`gsu_active`);

-- PATCH: Store who took a survey
ALTER TABLE `gems__tokens` ADD `gto_by` bigint(20) unsigned NULL AFTER  `gto_in_surveyor`;

-- GEMS VERSION: 30
-- PATCH: Round description only when needed
ALTER TABLE `gems__rounds` CHANGE `gro_round_description` `gro_round_description` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL

-- GEMS VERSION: 34
-- PATCH: Clear the surveys list
UPDATE gems__surveys SET gsu_active = 0 WHERE gsu_id_primary_group IS NULL AND gsu_active = 1;

-- GEMS VERSION: 35
-- PATCH: Add gsf_reset_key and 'gsf_reset_req columns
ALTER TABLE `gems__staff` ADD `gsf_reset_key` varchar(64) NULL AFTER `gsf_phone_1`;
ALTER TABLE `gems__staff` ADD `gsf_reset_req`timestamp NULL AFTER `gsf_reset_key`;

-- PATCH: Add gtr_organizations to tracks
ALTER TABLE `gems__tracks` ADD `gtr_organizations` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gtr_track_type` ;
UPDATE gems__tracks
    SET  `gtr_organizations` = (SELECT CONCAT('|', CONVERT(GROUP_CONCAT(gor_id_organization SEPARATOR '|'), CHAR), '|') as orgs FROM gems__organizations WHERE gor_active=1)
    WHERE gtr_active = 1;

-- PATCH: Gewijzigd track model
ALTER TABLE `gems__tracks` ADD `gtr_track_model` VARCHAR(64) NOT NULL DEFAULT 'TrackModel' AFTER `gtr_track_type`;
ALTER TABLE `gems__rounds` ADD `gro_used_date_order` INT(4) NULL AFTER `gro_used_date`,
    ADD `gro_used_date_field` VARCHAR(16) NULL AFTER `gro_used_date_order`;

-- GEMS VERSION: 36
-- PATCH: Store number of failed login attempts
ALTER TABLE `gems__staff` ADD `gsf_failed_logins` int(11) unsigned not null default 0 AFTER `gsf_active`;
ALTER TABLE `gems__staff` ADD `gsf_last_failed` timestamp null AFTER `gsf_failed_logins`;

-- GEMS VERSION: 37
-- PATCH: Allow duplicate location name across patch levels
ALTER TABLE `gems__patches` DROP INDEX `gpa_location` ,
    ADD UNIQUE `gpa_location` ( `gpa_level` , `gpa_location` , `gpa_name` , `gpa_order` ) ;

-- PATCH: Survey event model
ALTER TABLE gems__surveys ADD gsu_completed_event varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gsu_survey_pdf;

-- PATCH: New token table
ALTER TABLE `gems__tokens` CHANGE `gto_in_surveyor` `gto_in_source` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `gems__tokens` ADD `gto_comment` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gto_result`;
ALTER TABLE `gems__tokens` ADD `gto_start_time` DATETIME NULL DEFAULT NULL AFTER `gto_next_mail_date`;
ALTER TABLE `gems__tokens` CHANGE `gto_completion_date` `gto_completion_time` DATETIME NULL DEFAULT NULL;
ALTER TABLE `gems__tokens` ADD `gto_duration_in_sec` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `gto_completion_time`;
ALTER TABLE `gems__tokens` ADD `gto_round_order` INT NOT NULL DEFAULT '10' AFTER `gto_id_survey`;
ALTER TABLE `gems__tokens` ADD `gto_round_description` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gto_round_order`;
ALTER TABLE `gems__tokens` CHANGE `gto_valid_from` `gto_valid_from` DATETIME NULL DEFAULT NULL,
                           CHANGE `gto_valid_until` `gto_valid_until` DATETIME NULL DEFAULT NULL;

UPDATE gems__tokens, gems__rounds
    SET gto_round_order = gro_id_order,
        gto_round_description = gro_round_description
    WHERE gto_id_round = gro_id_round;

ALTER TABLE `gems__tokens` DROP INDEX `gto_id_respondent_track`;
ALTER TABLE `gems__tokens` ADD INDEX `gto_id_respondent_track` ( `gto_id_respondent_track` , `gto_round_order` );

-- PATCH: Rounds events
ALTER TABLE `gems__rounds` ADD `gro_changed_event` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gro_round_description`;

-- PATCH: Track end time
ALTER TABLE `gems__respondent2track` CHANGE `gr2t_start_date` `gr2t_start_date` DATETIME NULL DEFAULT NULL;
ALTER TABLE `gems__respondent2track` ADD `gr2t_end_date` DATETIME NULL DEFAULT NULL AFTER `gr2t_start_date`;

-- PATCH: New reception code
INSERT INTO `gems__reception_codes`
    (`grc_id_reception_code`, `grc_description`, `grc_success`, `grc_for_surveys`, `grc_redo_survey`, `grc_for_tracks`, `grc_for_respondents`, `grc_overwrite_answers`, `grc_active`, `grc_changed`, `grc_changed_by`, `grc_created`, `grc_created_by`)
    VALUES
    ('skip', 'Skipped by calculation', 0, 1, 0, 0, 0, 0, 0, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 1);

-- PATCH: Always a track_field for a track
INSERT INTO gems__track_fields
        (gtf_id_track, gtf_id_order, gtf_field_name,
            gtf_field_values, gtf_field_type, gtf_required,
            gtf_changed, gtf_changed_by, gtf_created, gtf_created_by)
    SELECT gtr_id_track as gtf_id_track, 10 as gtf_id_order, 'Description' as gtf_field_name,
            null as gtf_field_values, 'text' as gtf_field_type, 1 as gtf_required,
            CURRENT_TIMESTAMP as gtf_changed, 1 as gtf_changed_by, CURRENT_TIMESTAMP as gtf_created, 1 as gtf_created_by
        FROM gems__tracks WHERE gtr_id_track NOT IN (SELECT gtf_id_track FROM gems__track_fields);

-- GEMS VERSION: 38
-- PATCH: Update source classes
UPDATE gems__sources
    SET gso_ls_class = SUBSTRING(gso_ls_class, 13)
    WHERE SUBSTRING(gso_ls_class, 1, 12) = 'Gems_Source_';

-- PATCH: Start using track engine classes
ALTER TABLE `gems__tracks` ADD `gtr_track_class` varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default ''
    AFTER `gtr_track_model`;

UPDATE `gems__tracks`
   SET `gtr_track_class` =
       CASE
           WHEN gtr_track_type = 'S' THEN 'SingleSurveyEngine'
           WHEN gtr_track_model = 'NewTrackModel' THEN 'AnyStepEngine'
           ELSE 'NextStepEngine'
       END;

ALTER TABLE `gems__tracks` CHANGE `gtr_track_class` `gtr_track_class` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- PATCH: Survey event before answering model
ALTER TABLE gems__surveys ADD gsu_beforeanswering_event varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gsu_survey_pdf;

-- PATCH: Change bsn length to store hash instead of value
ALTER TABLE `gems__respondents` CHANGE `grs_bsn` `grs_bsn` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- GEMS VERSION: 39
-- PATCH: Organization signatures

ALTER TABLE `gems__organizations` ADD `gor_welcome` TEXT NULL DEFAULT NULL AFTER `gor_contact_email`;
ALTER TABLE `gems__organizations` ADD `gor_signature` TEXT NULL DEFAULT NULL AFTER `gor_welcome` ;

-- PATCH: Mail templates per organization
ALTER TABLE `gems__mail_templates` ADD `gmt_organizations` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `gmt_body`;
UPDATE gems__mail_templates SET gmt_organizations = (SELECT CONCAT('|', GROUP_CONCAT(gor_id_organization SEPARATOR '|'), '|') FROM gems__organizations);

-- GEMS VERSION: 40
-- PATCH: Organization codes
ALTER TABLE `gems__organizations` ADD gor_code            varchar(20)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' AFTER gor_name;

-- PATCH: Extra mail logging
RENAME TABLE gems__respondent_communications TO gems__log_respondent_communications;

ALTER TABLE gems__log_respondent_communications ADD grco_sender     varchar(120) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER grco_address;
ALTER TABLE gems__log_respondent_communications ADD grco_id_message bigint unsigned null references gems__mail_templates (gmt_id_message) AFTER grco_comments;

-- GEMS VERSION: 41
-- PATCH: Corrected misspelling of gtr_organisations
ALTER TABLE gems__tracks CHANGE gtr_organisations gtr_organizations varchar(250) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

-- PATCH: Assign maintenance mode toggle to super role
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges,',pr.maintenance') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.maintenance%';

-- GEMS VERSION: 42
-- PATCH: Add mail actions to admin role
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.mail.log') WHERE grl_name = 'admin' AND grl_privileges NOT LIKE '%pr.mail.log%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.mail.server,pr.mail.server.create,pr.mail.server.delete,pr.mail.server.edit') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.mail.server%';
UPDATE gems__roles SET grl_privileges = CONCAT(grl_privileges, ',pr.mail.job,pr.mail.job.create,pr.mail.job.delete,pr.mail.job.edit') WHERE grl_name = 'super' AND grl_privileges NOT LIKE '%pr.mail.job%';

-- PATCH: Set default for new rounds at days
ALTER TABLE `gems__round_periods` CHANGE `grp_valid_after_unit` `grp_valid_after_unit` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'D',
    CHANGE `grp_valid_for_unit` `grp_valid_for_unit` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'D';

-- PATCH: New user login structure
CREATE TABLE if not exists gems__user_ids (
        gui_id_user          bigint unsigned not null,

        gui_created          timestamp not null,

        PRIMARY KEY (gui_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__user_logins (
        gul_id_user          bigint unsigned not null auto_increment,

        gul_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gul_id_organization  bigint not null references gems__organizations (gor_id_organization),

        gul_user_class       varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'NoLogin',
        gul_can_login        boolean not null default 1,

        gul_changed          timestamp not null default current_timestamp on update current_timestamp,
        gul_changed_by       bigint unsigned not null,
        gul_created          timestamp not null,
        gul_created_by       bigint unsigned not null,

        PRIMARY KEY (gul_id_user),
        UNIQUE (gul_login, gul_id_organization)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 10001
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__user_login_attempts (
        gula_login            varchar(30) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gula_id_organization  bigint not null references gems__organizations (gor_id_organization),

    	gula_failed_logins    int(11) unsigned not null default 0,
        gula_last_failed      timestamp null,

        PRIMARY KEY (gula_login, gula_id_organization)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

CREATE TABLE if not exists gems__user_passwords (
        gup_id_user          bigint unsigned not null references gems__user_logins (gul_id_user),

        gup_password         varchar(32) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gup_reset_key        varchar(64) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gup_reset_requested  timestamp null,
        gup_reset_required   boolean not null default 0,

        gup_changed          timestamp not null default current_timestamp on update current_timestamp,
        gup_changed_by       bigint unsigned not null,
        gup_created          timestamp not null,
        gup_created_by       bigint unsigned not null,

        PRIMARY KEY (gup_id_user)
    )
    ENGINE=InnoDB
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO gems__user_logins (gul_login, gul_id_organization, gul_user_class,
                gul_can_login,
                gul_changed, gul_changed_by, gul_created, gul_created_by)
    SELECT gsf_login, gsf_id_organization, 'OldStaffUser',
                gsf_active,
                gsf_changed, gsf_changed_by, gsf_created, gsf_created_by
        FROM gems__staff WHERE gsf_login IS NOT NULL AND
            gsf_id_organization IS NOT NULL AND
            gsf_id_organization != 0 AND
            (gsf_login, gsf_id_organization) NOT IN (SELECT gul_login, gul_id_organization FROM gems__user_logins);

ALTER TABLE `gems__staff` CHANGE `gsf_id_user` `gsf_id_user` BIGINT( 20 ) UNSIGNED NOT NULL;

ALTER TABLE `gems__staff` CHANGE `gsf_password` `gsf_password` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `gems__staff` ADD UNIQUE `gesf_login` (`gsf_login`, `gsf_id_organization`);

ALTER TABLE gems__organizations ADD gor_style varchar(15) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gems' AFTER gor_signature;

INSERT INTO gems__user_ids (gui_id_user, gui_created)
    SELECT gsf_id_user, gsf_created FROM gems__staff WHERE gsf_id_user NOT IN (SELECT gui_id_user FROM gems__user_ids);

INSERT INTO gems__user_ids (gui_id_user, gui_created)
    SELECT grs_id_user, grs_created FROM gems__respondents WHERE grs_id_user NOT IN (SELECT gui_id_user FROM gems__user_ids);

-- PATCH: Extra information for track fields
ALTER TABLE gems__track_fields ADD gtf_field_code varchar(20) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gtf_field_name,
    ADD gtf_field_description varchar(200) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gtf_field_code,
    ADD gtf_readonly boolean not null default false AFTER gtf_required;

-- PATCH: Change Burger Service Nummer to Social Security Number
ALTER TABLE `gems__respondents` CHANGE `grs_bsn` `grs_ssn` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- PATCH: Extending organizations

ALTER TABLE `gems__organizations` ADD UNIQUE INDEX (`gor_code`);

ALTER TABLE `gems__organizations` ADD gor_accessible_by text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null AFTER gor_task,
    ADD gor_has_patients boolean not null default 1 AFTER gor_iso_lang,
    ADD gor_add_patients boolean not null default 1 AFTER gor_has_patients;

UPDATE `gems__organizations` SET gor_has_patients = COALESCE((SELECT 1 FROM gems__respondent2org WHERE gr2o_id_organization = gor_id_organization GROUP BY gr2o_id_organization), 0);

-- PATCH: Log failed logins
INSERT INTO  `zsd`.`gems__log_actions` (`glac_id_action`, `glac_name`, `glac_change`, `glac_log`, `glac_created`)
    VALUES (NULL ,  'loginFail',  '0',  '1', CURRENT_TIMESTAMP);