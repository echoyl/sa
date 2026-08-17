-- ----------------------------
-- Table structure for la_workflow_log
-- ----------------------------
DROP TABLE IF EXISTS `la_workflow_log`;
CREATE TABLE `la_workflow_log`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int NOT NULL DEFAULT 0 COMMENT '操作用户',
  `node_id` int NOT NULL DEFAULT 0 COMMENT '流程id',
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作动作',
  `state` int NOT NULL DEFAULT 0 COMMENT '操作状态',
  `model_id` int NOT NULL DEFAULT 0 COMMENT '数据关联id',
  `updated_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `displayorder` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  `workflow_id` int NOT NULL DEFAULT 0 COMMENT '工作流id',
  `user_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '可操作用户id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 123 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of la_workflow_log
-- ----------------------------

-- ----------------------------
-- Table structure for la_workflow_node
-- ----------------------------
DROP TABLE IF EXISTS `la_workflow_node`;
CREATE TABLE `la_workflow_node`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置',
  `upstream_id` int NOT NULL DEFAULT 0 COMMENT '上个流程id',
  `downstream_id` int NOT NULL DEFAULT 0 COMMENT '下个流程id',
  `workflow_id` int NOT NULL DEFAULT 0 COMMENT '所属工作流',
  `updated_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `displayorder` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of la_workflow_node
-- ----------------------------

-- ----------------------------
-- Table structure for la_workflow_workflow
-- ----------------------------
DROP TABLE IF EXISTS `la_workflow_workflow`;
CREATE TABLE `la_workflow_workflow`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `state` int NOT NULL DEFAULT 0 COMMENT '状态',
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标识',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置',
  `updated_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `displayorder` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of la_workflow_workflow
-- ----------------------------
