-- --------------------------------------------------------
-- Host:                         142.93.104.116
-- Server version:               8.0.44-0ubuntu0.24.04.1 - (Ubuntu)
-- Server OS:                    Linux
-- HeidiSQL Version:             12.0.0.6468
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE = @@TIME_ZONE */;
/*!40103 SET TIME_ZONE = '+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

-- Dumping structure for table bilo.data
CREATE TABLE IF NOT EXISTS `data`
(
    `id_record`      bigint unsigned NOT NULL,
    `dated`          datetime        NOT NULL,
    `id_source`      bigint          NOT NULL,
    `id_destination` bigint          NOT NULL,
    `record_type`    tinyint(1)      NOT NULL,
    `val`            decimal(12, 2)  NOT NULL,
    `id_unit`        int             NOT NULL,
    `id_reference`   int             NOT NULL,
    `autostamp`      timestamp(6)    NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id_record`),
    KEY `dated` (`dated`, `id_destination`, `id_reference`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.data_agg
CREATE TABLE IF NOT EXISTS `data_agg`
(
    `dated`          datetime                NOT NULL,
    `id_destination` bigint unsigned         NOT NULL,
    `id_reference`   bigint unsigned         NOT NULL,
    `count_positive` int unsigned            NOT NULL,
    `count_negative` int unsigned            NOT NULL,
    `sum_positive`   decimal(20, 2) unsigned NOT NULL,
    `sum_negative`   decimal(20, 2) unsigned NOT NULL,
    PRIMARY KEY (`dated`, `id_destination`, `id_reference`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.destinations
CREATE TABLE IF NOT EXISTS `destinations`
(
    `id_destination` bigint unsigned NOT NULL AUTO_INCREMENT,
    `destinationId`  varchar(50)     NOT NULL,
    PRIMARY KEY (`id_destination`),
    UNIQUE KEY `destinationId` (`destinationId`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 6835
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.queues
CREATE TABLE IF NOT EXISTS `queues`
(
    `id`            bigint unsigned   NOT NULL AUTO_INCREMENT,
    `queue_id`      smallint unsigned NOT NULL,
    `claimed`       tinyint unsigned  NOT NULL,
    `deleted`       tinyint unsigned  NOT NULL,
    `process_after` datetime          NOT NULL,
    PRIMARY KEY (`id`),
    KEY `queue_id` (`queue_id`, `deleted`, `claimed`, `process_after`) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 18504
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.queue_data
CREATE TABLE IF NOT EXISTS `queue_data`
(
    `id`  bigint unsigned NOT NULL,
    `msg` json            NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.records
CREATE TABLE IF NOT EXISTS `records`
(
    `id_record` bigint unsigned                                              NOT NULL AUTO_INCREMENT,
    `recordId`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
    PRIMARY KEY (`id_record`),
    UNIQUE KEY `recordId` (`recordId`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 10666
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.refs
CREATE TABLE IF NOT EXISTS `refs`
(
    `id_reference` bigint unsigned                                              NOT NULL AUTO_INCREMENT,
    `ref`          varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
    PRIMARY KEY (`id_reference`),
    UNIQUE KEY `reference` (`ref`) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 5925
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.search_requests
CREATE TABLE IF NOT EXISTS `search_requests`
(
    `id_search_request` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
    `search_type`       tinyint unsigned                                          NOT NULL DEFAULT '0',
    `status`            tinyint unsigned                                          NOT NULL,
    `autostamp`         timestamp                                                 NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_search_request`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.sources
CREATE TABLE IF NOT EXISTS `sources`
(
    `id_source` bigint unsigned                                              NOT NULL AUTO_INCREMENT,
    `sourceId`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
    PRIMARY KEY (`id_source`),
    UNIQUE KEY `sourceId` (`sourceId`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 6832
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table bilo.to_aggregate
CREATE TABLE IF NOT EXISTS `to_aggregate`
(
    `id`             int unsigned            NOT NULL AUTO_INCREMENT,
    `dated`          datetime                NOT NULL,
    `id_destination` bigint unsigned         NOT NULL,
    `id_reference`   bigint unsigned         NOT NULL,
    `val`            decimal(14, 2) unsigned NOT NULL DEFAULT '0.00',
    `positive`       tinyint unsigned        NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci
  ROW_FORMAT = DYNAMIC;

-- Data exporting was unselected.

-- Dumping structure for table bilo.units
CREATE TABLE IF NOT EXISTS `units`
(
    `id_unit` int unsigned NOT NULL AUTO_INCREMENT,
    `unit`    varchar(50)  NOT NULL,
    PRIMARY KEY (`id_unit`),
    UNIQUE KEY `unit` (`unit`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 840
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE = IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE = IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS = IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES = IFNULL(@OLD_SQL_NOTES, 1) */;
