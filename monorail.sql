/*
 Navicat MySQL Data Transfer

 Source Server         : local
 Source Server Version : 50145
 Source Host           : localhost
 Source Database       : monorail

 Target Server Version : 50145
 File Encoding         : utf-8

 Date: 12/22/2021 13:20:08 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `chats`
-- ----------------------------
DROP TABLE IF EXISTS `chats`;
CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `player_name` varchar(255) NOT NULL,
  `chat_text` varchar(2000) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `created_date` (`created_date`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

-- ----------------------------
--  Table structure for `emails`
-- ----------------------------
DROP TABLE IF EXISTS `emails`;
CREATE TABLE `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `last_sent_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `last_sent_date` (`last_sent_date`),
  KEY `created_date` (`created_date`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

-- ----------------------------
--  Table structure for `games`
-- ----------------------------
DROP TABLE IF EXISTS `games`;
CREATE TABLE `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_key` varchar(50) NOT NULL,
  `player1` varchar(255) NOT NULL,
  `player2` varchar(255) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `board` varchar(5000) NOT NULL,
  `last_player` tinyint(4) NOT NULL,
  `tiles_used` int(11) NOT NULL,
  `player1_quit` tinyint(4) NOT NULL,
  `player2_quit` tinyint(4) NOT NULL,
  `declared_impossible` tinyint(4) NOT NULL,
  `winner` tinyint(4) NOT NULL,
  `public` tinyint(4) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `game_key` (`game_key`),
  KEY `player1` (`player1`),
  KEY `player2` (`player2`),
  KEY `created_date` (`created_date`),
  KEY `public` (`public`),
  KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=89 DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
