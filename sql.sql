CREATE TABLE `log` (
 `log_id` bigint(24) unsigned NOT NULL AUTO_INCREMENT,
 `log_name` varchar(255) NOT NULL,
 `log_value` varchar(255) NOT NULL,
 `log_value2` TEXT NOT NULL,
 `log_value3` varchar(125) NOT NULL,
 `log_client` varchar(255) NOT NULL,
 `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `log_type` varchar(2) NOT NULL DEFAULT 'l',
 PRIMARY KEY (`log_id`),
 KEY `log_name` (`log_name`),
 KEY `log_value` (`log_value`),
 KEY `log_client` (`log_client`),
 KEY `log_type` (`log_type`),
 KEY `log_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1