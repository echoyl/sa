---
name: deadmin
description: DeAdmin后台管理系统的开发助手。当用户需要创建、修改或管理后台模块、菜单、模型、关联关系时使用此技能。适用于：创建后台管理系统、添加管理菜单、设计数据模型、配置表单字段、设置列表展示、创建分类模型、建立模型关联、创建员工模块、用户管理模块等后台开发任务。只要涉及DeAdmin后台管理操作，无论用户是否明确提到"deadmin"，都应该使用此技能。
---

# DeAdmin 后台管理

只通过后台 API 操作，**不直接修改 PHP 文件**。接口字段详情见 `Apis.md`。

## 标准流程

1. **登录** 取 token
2. **查文档** `Apis.md`（菜单 3.x / 模型 4.x / 关联 5.x）
3. **建模型**：文件夹(type=0) → 模型(type=1)
4. **建关联**：建关系 + 重存模型
5. **建菜单**：首次建 → 带 id 再存一次
6. **格式化**：`GET dev/formatFile/{id}` 生成 PHP 文件

## 铁律

- 只用 API，禁止直接改 PHP 文件
- **请求体必须 UTF-8 无 BOM**，否则字段被静默解析为空
- **默认值必须显式传**，不要依赖数据库默认值
- 模型 `name` 禁止下划线（用 `dailymenu` 不是 `daily_menu`）；表名自动拼为 `上级名_模型名`
- 新增数据 `base.id` 传 `0`（不是 `""`）

### 登录

读 `.env` 的 `APP_URL`；为 `/` 或不存在则询问用户（默认 `http://localhost:8000`）。接口前缀 `/sadmin`。

```powershell
Invoke-WebRequest -Uri "$url/sadmin/login" -Method POST `
  -Body "username=admin&password=123456&loginType=password" `
  -ContentType "application/x-www-form-urlencoded" -UseBasicParsing
```

返回 `access_token`，后续请求带 `Authorization: Bearer {token}`。

验证码错误时清缓存后重试：

```powershell
Remove-Item -Path "storage/framework/cache/data/*" -Exclude ".gitignore" -Force
```

### 请求体编码（重要）

PowerShell 发 JSON 必须写 UTF-8 无 BOM 文件，再 `-InFile`：

```powershell
$json = '{"base":{"title":"新闻","name":"news"}}'
[System.IO.File]::WriteAllText("req.json", $json, (New-Object System.Text.UTF8Encoding($false)))
Invoke-WebRequest -Uri $url -Method POST -InFile "req.json" -ContentType "application/json; charset=utf-8" -Headers $headers
```

带 BOM / UTF-16 / 内联 `ConvertTo-Json -Body` 都会让字段变空（表现为"收不到 POST 数据"）。

**不要用 `Get-Content -Raw` 读取请求体文件再转发**：PS 5.1 对无 BOM 的 UTF-8 文件按 ANSI 解码，中文全部损坏，请求解析失败会静默建出 `name/type/parent_id` 全空的垃圾记录。改用直发 -InFile（请求文件直接写成合法 UTF-8 JSON），或用 `[System.IO.File]::ReadAllText`（可正确识别 UTF-8）。

## 模型

### 类型

- `type=0` 文件夹（无字段）；`type=1` 模型（配字段）
- `leixing`：`normal` 默认 / `category` 分类（自带 parent_id）/ `auth` 可登录

### 必备字段（每个模型都要，显式默认值）

```json
[
  {"title":"ID","name":"id","type":"int","form_type":""},
  {"title":"状态","name":"state","type":"int","default":1,"form_type":"switch","table_menu":true},
  {"title":"排序","name":"displayorder","type":"int","default":0,"form_type":"digit"}
]
```

### 字段类型自动映射

| 字段 | form_type |
|---|---|
| state / open / status | `switch` |
| image / avatar / titlepic / pics | `image` |
| file / doc | `file` |
| desc / intro | `textarea` |
| content / detail | `tinyEditor` |
| category_id / group_id / role_id | `select` |
| tag_ids | `selects` |
| lat / lng | `mapInput` |

### 特殊字段

- **选择器**：关联模型名称字段非 `title` 时加 `"setting":{"label":"name"}`
- **省市区**：`province`(form_type=`pca`) + `city` + `area`（后两者无 form_type）；菜单只显示 province 并配 `can_search`
- **地图**：`lat`(form_type=`mapInput`) + `lng`（无 form_type），菜单中不显示
- **JSON 选项**：`options` 用 `type=text, form_type=json`，菜单中配 `valueType=formList`

### 保存模型

`POST dev/model`，body：

```json
{"base":{"title":"新闻","name":"news","type":1,"parent_id":100,"leixing":"normal","columns":[...]}}
```

## 关联

| type | 含义 |
|---|---|
| `one` | 一对一 |
| `many` | 一对多 |
| `cascaders` | 多对多 |

`POST dev/relation`，body：

```json
{"base":{"title":"分类","name":"category","model_id":1001,"foreign_model_id":1002,"type":"one","local_key":"category_id","foreign_key":"id","is_with":true}}
```

- `local_key` = 当前模型字段，`foreign_key` = 关联模型字段
- `is_with:true` 列表带上关联数据
- **建完关联后必须重新保存模型**

## 菜单

- `page_type`：`table`(默认) / `category` / `form` / `iframe`
- `open_type`：`drawer`(默认) / `page` / `modal`
- 必须显式传 `page_type` 和 `open_type`

`POST dev/menu`，body：

```json
{"base":{"title":"题目","path":"question","parent_id":10028,"type":"deadmin","admin_model_id":10017,"page_type":"table","open_type":"drawer","table_config":[...],"form_config":{...}}}
```

**table_config**

```json
[
  {"key":"id","props":{"title":"ID"}},
  {"key":"title","props":{"title":"标题"}},
  {"key":"state","props":{"title":"状态"},"table_menu":[1]},
  {"key":"option","props":{"title":"操作"}}
]
```

- 关联字段用数组 key：`{"key":["category","title"],"props":{"title":"分类"}}`
- `can_search:[1]` 可搜索；`table_menu:[1]` 表格内可切换开关

**form_config**

```json
{"tabs":[{"tab":{"title":"基础信息"},"config":[
  {"columns":[{"key":"title","props":{"title":"标题"}},{"key":"category_id","props":{"title":"分类"}}]},
  {"columns":[{"key":"content","props":{"title":"内容"},"props":{"span":24}}]}
]}]}
```

### 首次创建菜单

首次（id=0）不保存 `table_config` / `form_config`。必须：建菜单拿 id → 带 id 再存一次。

## 完整示例：题目模块

1. 文件夹：`{"title":"学生","name":"student","type":0,"parent_id":0}`
2. 模型：`{"title":"题目","name":"question","type":1,"parent_id":<文件夹id>,"leixing":"normal","columns":[默认三字段, {"title":"题干","name":"title","type":"varchar","length":255},{"title":"选项","name":"options","type":"text","form_type":"json"}]}`
3. 菜单：`{"title":"题目","path":"question","parent_id":<父菜单id>,"type":"deadmin","admin_model_id":<模型id>,"page_type":"table","open_type":"drawer"}` → 拿 id 再存 config
4. 格式化：`GET dev/formatFile/<模型id>`
5. 入库：`POST /sadmin/student/question`，body `{"base":{"id":0,...}}`

## 常见错误

| 现象 | 原因 | 解决 |
|---|---|---|
| 中文乱码 / 字段为空 | 请求体带 BOM 或非 UTF-8 | UTF-8 无 BOM 文件 + `-InFile` |
| 建出空记录（name/type 全丢） | 用 `Get-Content -Raw` 转发文件按 ANSI 读 | 直接 `-InFile` 发合法 UTF-8 JSON |
| 图形验证码错误 | 缓存 / 失败次数锁定 | 清 `storage/framework/cache/data` |
| 菜单配置为空 | 首次创建不保存 config | 带 id 再存一次 |
| 关联不生效 | 未重存模型 | 建关联后重新保存模型 |
| 缺 id/state/displayorder | 未显式添加 | 创建时补齐三字段 |
| 新增报 1366 | `base.id` 为空串 | 传 `"id":0` |

## API 速查

| 操作 | 接口 | 方法 |
|---|---|---|
| 登录 | `/sadmin/login` | POST |
| 菜单列表 / 详情 | `/sadmin/dev/menu`、`/sadmin/dev/menu/show?id={id}` | GET |
| 保存菜单 | `/sadmin/dev/menu` | POST |
| 模型列表 / 详情 | `/sadmin/dev/model`、`/sadmin/dev/model/show?id={id}` | GET |
| 保存模型 | `/sadmin/dev/model` | POST |
| 格式化文件 | `/sadmin/dev/formatFile/{id}` | GET |
| 关联列表 | `/sadmin/dev/relation?model_id={id}` | GET |
| 保存关联 | `/sadmin/dev/relation` | POST |

完整字段定义见 `Apis.md`（字段类型：4.0.1 节）。
