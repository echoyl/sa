-- Table structure for la_wechat_miniprogram_account
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_miniprogram_account`;
CREATE TABLE `la_wechat_miniprogram_account`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `state` int NOT NULL DEFAULT 0 COMMENT '状态',
  `qrcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '二维码',
  `appid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'APPID',
  `secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'APP秘钥',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `appname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '小程序账号信息' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_miniprogram_account
-- ----------------------------
-- ----------------------------
-- Table structure for la_wechat_miniprogram_user
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_miniprogram_user`;
CREATE TABLE `la_wechat_miniprogram_user`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `mobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号码',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'openID',
  `gender` int NOT NULL DEFAULT 0 COMMENT '性别',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '城市',
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省份',
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '国家',
  `unionid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Unionid',
  `last_used_at` datetime NULL DEFAULT NULL COMMENT '最后使用时间',
  `state` int NOT NULL DEFAULT 0 COMMENT '状态',
  `appid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '小程序账号appid',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 55 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_miniprogram_user
-- ----------------------------
-- ----------------------------
-- Table structure for la_wechat_miniprogram_user_bind
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_miniprogram_user_bind`;
CREATE TABLE `la_wechat_miniprogram_user_bind`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 0,
  `openid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` datetime NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '类型 留着拓展',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_miniprogram_user_bind
-- ----------------------------
-- ----------------------------
