
CREATE TABLE if not exists gems__organizations (
        gor_id_organization bigint unsigned not null auto_increment,

        gor_name            varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gor_code            varchar(20)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_location        varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_url             varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_task            varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        -- A commy separated list of organization numbers that can look at respondents in this organization
        gor_accessible_by   text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_contact_name    varchar(50)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_contact_email   varchar(127) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,
        gor_welcome         text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'  null,
        gor_signature       text CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        gor_style           varchar(15)  CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null default 'gems',
        gor_iso_lang        char(2) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
                            not null default 'en' references gems__languages (gml_iso_lang),

        gor_has_patients    boolean not null default 1,
        gor_add_patients    boolean not null default 1,
        gor_active          boolean not null default 1,

        gor_changed         timestamp not null default current_timestamp on update current_timestamp,
        gor_changed_by      bigint unsigned not null,
        gor_created         timestamp not null,
        gor_created_by      bigint unsigned not null,

        PRIMARY KEY(gor_id_organization),
        UNIQUE (gor_code)
    )
    ENGINE=InnoDB
    AUTO_INCREMENT = 70
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
