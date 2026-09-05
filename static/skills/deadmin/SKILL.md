---
name: deadmin
description: DeAdmin后台管理系统的开发助手。当用户需要创建、修改或管理后台模块、菜单、模型、关联关系时使用此技能。适用于：创建后台管理系统、添加管理菜单、设计数据模型、配置表单字段、设置列表展示、创建分类模型、建立模型关联、创建员工模块、用户管理模块等后台开发任务。只要涉及DeAdmin后台管理操作，无论用户是否明确提到"deadmin"，都应该使用此技能。
---

# DeAdmin 后台管理助手

## 何时使用此技能

当用户需要进行以下操作时使用此技能：

- **创建或修改后台模块** - 添加新的管理功能模块
- **创建或修改菜单** - 配置后台导航菜单和页面
- **创建或修改模型** - 设计数据库表结构和字段
- **创建或修改关联** - 建立模型之间的关系
- **配置表单和列表** - 设置数据展示和编辑界面

**重要**：此技能只通过API接口操作，不直接修改项目文件。

## 快速开始

### 第一步：登录

任何操作前必须先登录获取token：

**确定项目URL：**

1. 首先读取项目根目录的 `.env` 文件
2. 如果 `APP_URL` 存在且**不是 `/`**，则使用该值作为项目URL
3. 如果 `APP_URL` 不存在或是 `/`，则询问用户选择：
   - 选项1：使用默认 `http://localhost:8000`
   - 选项2：用户输入当前项目URL

**登录请求：**

```bash
curl -X POST "{项目URL}/sadmin/login" \
  -H "Content-Type: application/json; charset=utf-8" \
  -d '{"username": "admin", "password": "123456"}'
```

响应中的 `access_token` 用于后续所有请求的认证。

### 第二步：读取API文档

详细接口文档在 `Apis.md` 中，包含：
- 菜单管理接口 (3.x)
- 模型管理接口 (4.x)
- 关联管理接口 (5.x)

## 核心规则

### 必须遵守

1. **【必须】操作前完整阅读本SKILL.md**
2. **【必须】请求体使用UTF-8编码** - 中文必须正确编码
3. **【必须】创建模型前阅读所有模型规则**
4. **【禁止】禁止直接修改PHP文件** - 所有操作通过API完成
5. **【必须】尊重默认值 - 以本md文档中指定的默认值为准** - 新增模型或菜单时，凡是文档中明确指定了默认值的字段/属性，都必须在请求体中显式带上该默认值，**不要把默认值寄托在数据库上**（数据库中的默认值可能与文档不一致）。例如：
   - 字段 `state` 默认 `1`（对应 md 中的 `"default": 1`）
   - 字段 `displayorder` 默认 `0`（对应 md 中的 `"default": 0`）
   - 菜单 `page_type` 默认 `table`、`open_type` 默认 `drawer`（见"菜单类型"）
   - 模型 `leixing` 默认 `normal`（见"模型类型说明"）
   - 其他文档中标注了默认值的项同理

   示例：新增模型时必须为 `state` 列显式传 `"default": 1`，为 `displayorder` 列显式传 `"default": 0`；新增菜单时必须显式指定 `"page_type": "table"` 和 `"open_type": "drawer"` 等默认值。

### PowerShell 中文编码

PowerShell 5.1 发送中文必须使用以下方式：

```powershell
$json = '{"base":{"title":"应用管理","name":"app"}}'
$utf8 = [System.Text.Encoding]::UTF8.GetBytes($json)
[System.IO.File]::WriteAllBytes("request.json", $utf8)
Invoke-WebRequest -Uri $url -Method POST -InFile "request.json" -ContentType "application/json; charset=utf-8" -Headers $headers
```

### 验证码处理

如果登录返回"图形验证码错误"，清除缓存后重试：

```powershell
Remove-Item -Path "storage/framework/cache/data/*" -Exclude ".gitignore" -Force
```

## 模型字段规则

### 字段类型自动映射

根据字段名称自动选择正确的 form_type：

| 字段特征 | form_type | 示例字段名 |
|---------|-----------|-----------|
| 开关/状态 | `switch` | state, open, status |
| 图片 | `image` | image, avatar, titlepic, pics |
| 文件 | `file` | file, doc |
| 描述 | `textarea` | desc, description, intro |
| 富文本 | `tinyEditor` | content, detail |
| 选择器 | `select` | category_id, group_id, role_id |
| 多选 | `selects` | tag_ids |
| 地图 | `mapInput` | lat, lng |

### 省市区配置

创建省市区需要三个字段：
- `province` - form_type: `pca`
- `city` - 无 form_type
- `area` - 无 form_type

菜单配置：只显示 `province` 字段，配置 `can_search`，label 设为"省市区"

### 选择器配置

当关联模型的名称字段不是默认的 `title` 时，需要配置：

```json
{
  "setting": {"label": "name"}
}
```

### 地图选点配置

创建 `lat` 和 `lng` 两个 varchar 字段：
- `lat` - form_type: `mapInput`
- `lng` - 无 form_type
- 菜单中不显示这两个字段
- 表单 label 配置为"经纬度"

## 使用方法

### 创建或修改模型

#### 基本流程

1. **创建文件夹** - 创建上级文件夹（type=0）
2. **创建模型** - 在文件夹下创建模型（type=1）
3. **创建关联** - 自动检测并创建关联关系
4. **格式化文件** - 调用格式化接口生成PHP文件

#### 模型命名规则

- **禁止使用下划线 `_`** - 使用 `dailymenu` 而非 `daily_menu`
- 数据表名自动拼接：`上级名_模型名`
- 子模型 name 不能包含上级文件夹名

#### 必须添加的默认字段

创建模型时必须包含以下3个字段，**且必须显式带上每个字段的默认值**（`state` 的 `default: 1`、`displayorder` 的 `default: 0`），因为数据库中的默认值可能不是文档指定的值：

```json
[
  {"title": "ID", "name": "id", "type": "int", "form_type": ""},
  {"title": "状态", "name": "state", "type": "int", "default": 1, "form_type": "switch", "table_menu": true},
  {"title": "排序", "name": "displayorder", "type": "int", "default": 0, "form_type": "digit"}
]
```

#### 模型类型说明

- `type=0` - 文件夹（不需要字段）
- `type=1` - 真实模型（需要配置字段）
- `leixing=normal` - 通用模型（默认）
- `leixing=category` - 分类模型（自动添加 parent_id）
- `leixing=auth` - 可登录模型

#### 示例：创建新闻模型

```json
{
  "base": {
    "title": "新闻",
    "name": "news",
    "type": 1,
    "parent_id": 100,
    "leixing": "normal",
    "columns": [
      {"title": "ID", "name": "id", "type": "int", "form_type": ""},
      {"title": "状态", "name": "state", "type": "int", "default": 1, "form_type": "switch", "table_menu": true},
      {"title": "排序", "name": "displayorder", "type": "int", "default": 0, "form_type": "digit"},
      {"title": "标题", "name": "title", "type": "varchar", "length": 255, "form_type": ""},
      {"title": "内容", "name": "content", "type": "text", "form_type": "tinyEditor"}
    ]
  }
}
```

### 创建或修改关联

#### 关联类型说明

| 类型 | 说明 | 示例 |
|------|------|------|
| `one` | 一对一 | 新闻有一个分类 |
| `many` | 一对多 | 新闻有多个评论 |
| `cascaders` | 多对多 | 新闻有多个标签 |

#### 关联字段命名规则

- 当前模型：`关联名称_id`（如 `category_id`）
- 关联模型：`id` 或 `foreign_key`

#### 示例：新闻-分类关联（hasOne）

```json
{
  "base": {
    "title": "分类",
    "name": "category",
    "model_id": 1001,
    "foreign_model_id": 1002,
    "type": "one",
    "local_key": "category_id",
    "foreign_key": "id",
    "is_with": true
  }
}
```

#### 示例：新闻-评论关联（hasMany）

```json
{
  "base": {
    "title": "评论",
    "name": "comments",
    "model_id": 1001,
    "foreign_model_id": 1003,
    "type": "many",
    "local_key": "id",
    "foreign_key": "news_id",
    "is_with": false
  }
}
```

#### 示例：新闻-标签关联（cascaders）

```json
{
  "base": {
    "title": "标签",
    "name": "tags",
    "model_id": 1001,
    "foreign_model_id": 1004,
    "type": "cascaders",
    "local_key": "tag_id",
    "foreign_key": "id",
    "is_with": false
  }
}
```

**重要**：单独创建关联后，必须重新调用"提交保存模型"接口

### 创建或修改菜单

#### 菜单类型

- `page_type=table` - 表格列表页面（**默认**）
- `page_type=category` - 分类管理页面
- `open_type=drawer` - 抽屉弹层打开（**默认**）
- `open_type=page` - 页面跳转打开

**重要**：新增菜单时必须显式指定 `page_type` 和 `open_type` 的值（默认分别为 `table` 和 `drawer`），**不能省略**，因为数据库中的默认值可能与文档不一致，以文档指定的默认值为准。

#### table_config 配置

```json
[
  {"key": "id", "props": {"title": "ID"}},
  {"key": "title", "props": {"title": "标题"}},
  {"key": "state", "props": {"title": "状态"}, "table_menu": [1]},
  {"key": "category", "props": {"title": "分类"}, "key": ["category", "name"]},
  {"key": "option", "props": {"title": "操作"}}
]
```

#### form_config 配置

```json
{
  "tabs": [
    {
      "tab": {"title": "基础信息"},
      "config": [
        {
          "columns": [
            {"key": "title", "props": {"title": "标题"}},
            {"key": "category_id", "props": {"title": "分类"}}
          ]
        },
        {
          "columns": [
            {"key": "content", "props": {"title": "内容"}, "props": {"span": 24}}
          ]
        }
      ]
    }
  ]
}
```

#### 首次创建注意事项

首次创建菜单（id=0）时，`table_config` 和 `form_config` 不会保存。必须：
1. 创建菜单获取 id
2. 立即用该 id 重新调用"提交保存菜单"接口

### 创建模块

#### 模块结构

```
模块文件夹 (type=0)
├── 模型1 (type=1)
├── 模型2 (type=1)
└── 关联关系
```

#### 创建步骤

1. 创建上级文件夹（type=0）
2. 在文件夹下创建各个模型（type=1）
3. 创建上级菜单
4. 在上级菜单下创建子菜单
5. 自动创建关联关系

## 使用示例

### 示例1：创建简单的文章模块

用户请求："帮我创建一个新闻管理模块"

**步骤：**

1. 登录获取 token
2. 创建文件夹（name="news"，type=0）
3. 创建新闻模型（name="news"，type=1）
   - 添加默认字段（id, state, displayorder）
   - 添加业务字段（title, content, image 等）
4. 创建菜单
   - 配置 table_config（列表字段）
   - 配置 form_config（表单字段）
5. 格式化模型文件

### 示例2：创建带分类的新闻模块

用户请求："创建新闻模块，需要分类功能"

**额外步骤：**

1. 创建分类模型（name="category"，leixing=category）
2. 创建关联关系（新闻 model_id → 分类 foreign_model_id）
3. 在菜单中配置分类选择器

### 示例3：创建员工管理模块

用户请求："创建员工模块，包含员工和角色管理"

**步骤：**

1. 创建员工文件夹（name="employee"，type=0）
2. 创建员工模型（name="employee"，leixing=auth）
3. 创建角色模型（name="role"）
4. 创建关联关系（员工 ↔ 角色）
5. 创建菜单结构

## 错误处理

### 常见问题

#### 1. 登录失败：图形验证码错误

**原因**：缓存数据导致验证码校验失败

**解决方案**：
```powershell
Remove-Item -Path "storage/framework/cache/data/*" -Exclude ".gitignore" -Force
```

#### 2. 中文数据显示为乱码

**原因**：请求体未使用 UTF-8 编码

**解决方案**：
- 使用 `[System.Text.Encoding]::UTF8.GetBytes()` 编码
- 或在 PowerShell 中使用 `-InFile` 参数

#### 3. 首次创建菜单后配置为空

**原因**：首次创建时 table_config 和 form_config 不会保存

**解决方案**：
1. 获取创建后的菜单 id
2. 立即用该 id 重新调用"提交保存菜单"接口

#### 4. 关联关系未生效

**原因**：单独创建关联后未重新保存模型

**解决方案**：创建关联后必须调用"提交保存模型"接口

#### 5. 模型字段缺少默认字段

**原因**：系统不会自动添加 id、state、displayorder 字段

**解决方案**：创建模型时必须显式添加这三个字段

## API 接口速查

| 操作 | 接口 | 方法 |
|------|------|------|
| 登录 | `/sadmin/login` | POST |
| 获取菜单列表 | `/sadmin/dev/menu` | GET |
| 获取菜单详情 | `/sadmin/dev/menu/show?id={id}` | GET |
| 保存菜单 | `/sadmin/dev/menu` | POST |
| 获取模型列表 | `/sadmin/dev/model` | GET |
| 获取模型详情 | `/sadmin/dev/model/show?id={id}` | GET |
| 保存模型 | `/sadmin/dev/model` | POST |
| 格式化模型文件 | `/sadmin/dev/formatFile/{id}` | GET |
| 获取关联列表 | `/sadmin/dev/relation?model_id={id}` | GET |
| 保存关联 | `/sadmin/dev/relation` | POST |

## 参考文档

- 完整 API 文档：`Apis.md`
- 字段类型详情：见 `Apis.md` 第 4.0.1 节
- form_type 列表：见 `Apis.md` 第 4.0.1 节
