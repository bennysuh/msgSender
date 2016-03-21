/*
Navicat MySQL Data Transfer

Source Server         : 本地
Source Server Version : 50611
Source Host           : 127.0.0.1:3306
Source Database       : weixin

Target Server Type    : MYSQL
Target Server Version : 50611
File Encoding         : 65001

Date: 2016-03-21 14:49:23
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `tp_login`
-- ----------------------------
DROP TABLE IF EXISTS `tp_login`;
CREATE TABLE `tp_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `uname` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `createtime` varchar(11) NOT NULL,
  `updatetime` varchar(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`uname`,`module`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

