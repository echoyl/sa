-- Table structure for la_wechat_pay
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_pay`;
CREATE TABLE `la_wechat_pay` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `mch_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商户号',
  `apikey` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商户秘钥',
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `appid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Appid',
  `cert` text COLLATE utf8mb4_general_ci COMMENT 'cert',
  `key` text COLLATE utf8mb4_general_ci COMMENT 'key',
  `state` int NOT NULL DEFAULT '0' COMMENT '状态',
  `created_at` datetime DEFAULT NULL COMMENT '生成时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  `apikey_v3` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商户秘钥v3',
  `displayorder` int NOT NULL DEFAULT '0' COMMENT '排序值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='微信支付配置';
-- ----------------------------
-- Records of la_wechat_pay
-- ----------------------------
-- ----------------------------
-- Table structure for la_wechat_pay_log
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_pay_log`;
CREATE TABLE `la_wechat_pay_log`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `money` int NOT NULL DEFAULT 0,
  `state` int NOT NULL DEFAULT 0 COMMENT '0-待支付 1-已支付 2-已退款',
  `pay_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `openid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `offiaccount_user_openid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `miniprogram_user_openid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `out_sn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `refund_out_sn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信退款订单号',
  `refund_sn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '本地退款订单号',
  `refund_at` datetime NULL DEFAULT NULL COMMENT '退款成功时间',
  `refund_money` int NULL DEFAULT 0 COMMENT '退款金额',
  `refund_state` int NOT NULL DEFAULT 0 COMMENT '是否已退款',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 23 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '微信支付表' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_pay_log
-- ----------------------------
-- ----------------------------
-- Table structure for la_wechat_pay_refund
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_pay_refund`;
CREATE TABLE `la_wechat_pay_refund`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `log_id` int NOT NULL DEFAULT 0 COMMENT '支付记录',
  `money` int NOT NULL DEFAULT 0 COMMENT '退款金额',
  `out_sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信退款订单号',
  `sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '本地退款订单号',
  `refund_at` datetime NULL DEFAULT NULL COMMENT '退款成功时间',
  `state` int NOT NULL DEFAULT 0 COMMENT '状态',
  `updated_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `displayorder` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_pay_refund
-- ----------------------------
-- ----------------------------
