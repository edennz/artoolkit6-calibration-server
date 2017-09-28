CREATE TABLE `calib_camera`.`calib_camera` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `timestamp` VARCHAR(25) NOT NULL,
    `remote_addr` VARCHAR(15) NOT NULL,
    `remote_addr_name` VARCHAR(1023) NULL,
    `device_id` VARCHAR(1023) NOT NULL,
    `device_id_manufacturer` VARCHAR(255) AS (SUBSTRING_INDEX(`device_id`, '/', 1)) STORED NOT NULL,
    `device_id_model` VARCHAR(255) AS (SUBSTRING_INDEX(SUBSTRING_INDEX(`device_id`, '/', -2), '/', 1)) STORED NOT NULL,
    `device_id_board` VARCHAR(255) AS (SUBSTRING_INDEX(`device_id`, '/', -1)) STORED NOT NULL,
    `focal_length` FLOAT DEFAULT 0.0,
    `camera_index` INT UNSIGNED NOT NULL,
    `camera_face` VARCHAR(255),
    `camera_width` INT UNSIGNED NOT NULL,
    `camera_height` INT UNSIGNED NOT NULL,
    `aspect_ratio` VARCHAR(45) NOT NULL,
    `err_min` FLOAT DEFAULT 0.0,
    `err_avg` FLOAT DEFAULT 0.0,
    `err_max` FLOAT DEFAULT 0.0,
    `camera_para_base64` VARCHAR(1023) NOT NULL,
    `os_name` VARCHAR(255) NULL,
    `os_arch` VARCHAR(255) NULL,
    `os_version` VARCHAR(255) NULL,
    `created_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_user` VARCHAR(255) NULL,
    `modified_timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modified_user` VARCHAR(255) NULL,
    `notes` VARCHAR(1023) NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `calib_camera`.`google_play_supported_devices` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `Retail Branding` VARCHAR(255),
    `Marketing Name` VARCHAR(255),
    `Device` VARCHAR(255),
    `Model` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
