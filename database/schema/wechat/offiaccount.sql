-- Table structure for la_wechat_offiaccount_account
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_offiaccount_account`;
CREATE TABLE `la_wechat_offiaccount_account`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `state` int NOT NULL DEFAULT 0 COMMENT '状态',
  `qrcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '二维码',
  `appid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'APPID',
  `secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'APP秘钥',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `appname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Token',
  `encodingaeskey` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Encodingaeskey',
  `subscribe_reply` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关注时回复',
  `auto_reply` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '自动回复',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '公众号账号信息' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_offiaccount_account
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_offiaccount_admin`;
CREATE TABLE `la_wechat_offiaccount_admin` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `state` int NOT NULL DEFAULT '1' COMMENT '状态',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '后台用户id',
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `displayorder` int NOT NULL DEFAULT '0' COMMENT '排序权重',
  `openid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'openid',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- ----------------------------
-- Table structure for la_wechat_offiaccount_menu
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_offiaccount_menu`;
CREATE TABLE `la_wechat_offiaccount_menu`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `wechat_offiaccount_id` int NOT NULL DEFAULT 0 COMMENT '公众号',
  `updated_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `displayorder` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  `open` int NOT NULL DEFAULT 0 COMMENT '启用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_offiaccount_menu
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_offiaccount_template`;
CREATE TABLE `la_wechat_offiaccount_template` (
  `appid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '所属公众号ID',
  `content` text COLLATE utf8mb4_general_ci COMMENT '模板内容',
  `created_at` datetime DEFAULT NULL COMMENT '生成时间',
  `deputy_industry` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '二级类目',
  `displayorder` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `example` text COLLATE utf8mb4_general_ci COMMENT '模板示例',
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `primary_industry` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '一级类目',
  `state` int NOT NULL DEFAULT '1' COMMENT '状态',
  `template_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板ID',
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板标题',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  `keys` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关键字',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `template_id` (`template_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS `la_wechat_offiaccount_templatemessage`;
CREATE TABLE `la_wechat_offiaccount_templatemessage` (
  `created_at` datetime DEFAULT NULL COMMENT '生成时间',
  `data` text COLLATE utf8mb4_general_ci COMMENT '消息数据',
  `desc` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `displayorder` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '事件名称',
  `state` int NOT NULL DEFAULT '1' COMMENT '状态',
  `template_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板ID',
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  `app_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '小程序ID',
  `app_param` text COLLATE utf8mb4_general_ci COMMENT '小程序页面参数',
  `app_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '小程序路径',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- ----------------------------
-- Table structure for la_wechat_offiaccount_user
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_offiaccount_user`;
CREATE TABLE `la_wechat_offiaccount_user`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `openid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `privilege` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `unionid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `status` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `subscribe` int NOT NULL DEFAULT 0 COMMENT '1已关注',
  `subscribe_scene` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `gender` int NOT NULL DEFAULT 0,
  `state` int NOT NULL DEFAULT 0 COMMENT '1|0',
  `account_id` int NOT NULL DEFAULT 0 COMMENT '公众号账号id',
  `last_used_at` datetime NULL DEFAULT NULL COMMENT '最后更新时间',
  `appid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '公众号appid',
  `subscribe_at` datetime NULL DEFAULT NULL COMMENT '关注时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 89 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_offiaccount_user
-- ----------------------------
-- ----------------------------
