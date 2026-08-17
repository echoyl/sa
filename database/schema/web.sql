-- ----------------------------
-- Table structure for la_web_menu
-- ----------------------------
DROP TABLE IF EXISTS `la_web_menu`;
CREATE TABLE `la_web_menu` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `small_title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '副标题',
  `parent_id` int NOT NULL DEFAULT '0' COMMENT '上级id',
  `displayorder` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `created_at` datetime DEFAULT NULL COMMENT '生成时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  `tpl` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `link` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '外链',
  `banner` text COLLATE utf8mb4_general_ci COMMENT '轮播图',
  `pagesize` int NOT NULL DEFAULT '0' COMMENT '分页量',
  `desc` text COLLATE utf8mb4_general_ci COMMENT '描述',
  `ext_cateid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '附加栏目id',
  `alias` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Url别名',
  `content_detail` text COLLATE utf8mb4_general_ci COMMENT '内容',
  `json` text COLLATE utf8mb4_general_ci COMMENT 'JSON配置',
  `specs` text COLLATE utf8mb4_general_ci COMMENT '属性',
  `content_id` int NOT NULL DEFAULT '0' COMMENT '内容ID',
  `category_id` int NOT NULL DEFAULT '0' COMMENT '分类ID',
  `_category_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '_分类ID',
  `blank` int NOT NULL DEFAULT '0' COMMENT '新窗口打开',
  `hits` int NOT NULL DEFAULT '0' COMMENT '点击数',
  `module` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模块类型',
  `state` int NOT NULL DEFAULT '1' COMMENT '状态',
  `params` text COLLATE utf8mb4_general_ci COMMENT '参数',
  `pagetype` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面类型',
  `top` int NOT NULL DEFAULT '0' COMMENT '头部显示',
  `bottom` int NOT NULL DEFAULT '0' COMMENT '底部显示',
  `category_all` int NOT NULL DEFAULT '0' COMMENT '是否显示子分类',
  `relate_menu_id` int NOT NULL DEFAULT '0' COMMENT '相关联菜单',
  `_relate_menu_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '_相关联菜单',
  `type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '类型',
  `admin_model_id` int NOT NULL DEFAULT '0' COMMENT '关联模型ID',
  `pics` text COLLATE utf8mb4_general_ci COMMENT '菜单图片',
  `hidden` int NOT NULL DEFAULT '0' COMMENT '是否隐藏',
  `index_show` int NOT NULL DEFAULT '0' COMMENT '首页显示',
  `list_show` int NOT NULL DEFAULT '0' COMMENT '列表显示',
  `title_en-US` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称-English',
  `title_zh-CN` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称-简体中文',
  `titlepic` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '封面图片',
  `category_show_bottom` int NOT NULL DEFAULT '0' COMMENT '子分类底部显示',
  `category_show_top` int NOT NULL DEFAULT '0' COMMENT '子分类头部显示',
  `category_default_first` int NOT NULL DEFAULT '1' COMMENT '默认首个分类',
  `title_zh-TW` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称-繁體中文',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of la_web_menu
-- ----------------------------
