-- Table structure for la_wechat_session
-- ----------------------------
DROP TABLE IF EXISTS `la_wechat_session`;
CREATE TABLE `la_wechat_session`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `openid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `unionid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `session_key`(`session_key`(191) ASC) USING BTREE,
  INDEX `openid`(`openid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Records of la_wechat_session
-- ----------------------------
INSERT INTO `la_wechat_session` VALUES (1, 'Rshqye0gtM7aKWGOqQCEug==', 'oxomE4ldDtTGPzr9M12YSOLpnffg', '2023-02-24 10:33:47', '2023-02-24 10:33:49', '');
-- ----------------------------
