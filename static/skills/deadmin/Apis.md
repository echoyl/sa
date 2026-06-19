# DeAdmin后台管理接口文档

## 1. 接口概述

本文档描述了用户管理相关的 RESTful API 接口规范。所有接口均使用 JSON 格式进行数据交互。

- **Base URL** : `/.env` 文件中的 `APP_URL`,如果没有则使用 `http://localhost:8000`
- **URL前缀**: `/.env` 文件中的 `APP_ADMIN_PREFIX`,如果没有则使用 `sadmin`
- **认证方式**: Bearer Token (在 Header 中携带 `Authorization: Bearer <token>`)

---

### Header 参数

| 参数名        | 类型   | 必填 | 描述                                  |
| :------------ | :----- | :--- | :------------------------------------ |
| Authorization | String | 是   | 身份验证令牌，格式为 `Bearer {token}` |
| Content-Type  | String | 是   | `application/json`                    |

### 响应参数

| 参数名 | 类型      | 描述                                             |
| :----- | :-------- | :----------------------------------------------- |
| code   | `Integer` | 业务状态码，`0` 表示成功                         |
| msg    | `String`  | 提示信息                                         |
| data   | `Any`     | 返回数据，可能是`对象`，`数组`或`字符串`或`null` |

以下接口响应参数若无特定说明，都包含以上参数。

### 全局错误码code说明

| HTTP 状态码 | 业务错误码 | 描述               | 处理建议                        |
| :---------- | :--------- | :----------------- | :------------------------------ |
| 200         | 0          | 请求成功           | -                               |
| 200         | 1001       | Token 无效或已过期 | 重新登录获取新 Token            |
| 400         | 40001      | 参数校验失败       | 检查请求参数是否符合规范        |
| 401         | 40100      | Token 无效或已过期 | 重新登录获取新 Token            |
| 403         | 40300      | 无权限访问该资源   | 确认当前用户角色权限            |
| 404         | 40401      | 资源不存在         | 检查资源 ID 是否正确            |
| 429         | 42900      | 请求过于频繁       | 稍后重试，注意限流策略          |
| 500         | 50000      | 服务器内部错误     | 请联系管理员并提供 X-Request-ID |

## 2. 用户相关

### 2.1 登录后台获取token

- **接口描述** : 用户通过账号和密码进行身份验证，成功后返回访问令牌（Access Token）。
- **请求路径**: `/login`
- **请求方法**: `POST`

#### 2.1.1请求参数 (Body)

| 参数名   | 类型   | 必填 | 描述                           | 示例值   |
| :------- | :----- | :--- | :----------------------------- | :------- |
| username | String | 是   | 用户名或注册邮箱               | `admin`  |
| password | String | 是   | 用户密码（建议前端加密后传输） | `123456` |

#### 2.1.2响应参数

| 参数名          | 类型   | 描述                        |
| :-------------- | :----- | :-------------------------- |
| data            | Object | 登录凭证数据对象            |
| └─ access_token | String | 登录token，用于后续接口鉴权 |
| └─ userinfo     | Object | 登录用户信息                |
| └─ setting      | Object | 后台配置信息                |

#### 2.1.3请求示例

```bash
curl -X POST "http://localhost:8000/sadmin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "123456"
  }'
```

#### 2.1.4成功响应示例

**HTTP Status**: `200 OK`

```json
{
	"code": 0,
	"msg": "登录成功，页面跳转中...",
	"data": {
		"access_token": "514|7QpAJGI1NWefL75R5T53ab5oc0NkNRQlphAs6Z3A1822cd49",
		"setting": {},
		"userinfo": { "id": 1 }
	}
}
```

#### 2.1.5错误响应示例

**HTTP Status**: `404 Not Found`

```json
{
	"code": 1,
	"msg": "User not found",
	"data": null
}
```

## 3.菜单相关

### 3.0 菜单字段说明

| 字段名称       | 类型         | 默认值         | 描述                                          |
| :------------- | :----------- | :------------- | :-------------------------------------------- |
| id             | int          | AUTO_INCREMENT | 主键ID                                        |
| title          | varchar(500) | ''             | 菜单标题                                      |
| title_locale   | longtext     | NULL           | 菜单名称多语言内容                            |
| path           | varchar(500) | ''             | 菜单路径                                      |
| parent_id      | int          | 0              | 父级菜单ID                                    |
| displayorder   | int          | 0              | 显示排序                                      |
| status         | int          | 0              | 菜单隐藏控制 (1:显示, 0:隐藏)                 |
| icon           | varchar(500) | ''             | 菜单图标                                      |
| desc           | text         | NULL           | 额外数据 (json格式)                           |
| created_at     | datetime     | NULL           | 创建时间                                      |
| updated_at     | datetime     | NULL           | 更新时间                                      |
| state          | int          | 0              | 状态枚举 (1或0)                               |
| category_id    | int          | 0              | 模型的分类id值                                |
| router         | varchar(50)  | ''             | 菜单指定路由 (为空时自动根据父级拼接)         |
| perms          | varchar(500) | ''             | 菜单指定的子权限 (json格式)                   |
| type           | varchar(500) | system         | 菜单类型 (system:系统菜单, 其它:对应项目菜单) |
| admin_model_id | int          | 0              | 所选的模型id                                  |
| table_config   | text         | NULL           | 列表展示列的配置信息                          |
| form_config    | text         | NULL           | 表单展示的配置信息                            |
| other_config   | text         | NULL           | 其它额外配置信息                              |
| page_type      | varchar(255) | ''             | 菜单页面类型 (category 或 table)              |
| open_type      | varchar(255) | page           | form页面打开方式 (page 或 drawer)             |
| addable        | int          | 1              | 是否可以新增                                  |
| editable       | int          | 1              | 表单是否可以提交                              |
| deleteable     | int          | 1              | 数据是否可以删除                              |
| setting        | text         | NULL           | 其它设置集合                                  |

#### 3.0.1 `table_config` 与 `form_config`字段

table_config结构为 column[]

form_config结构为

```json
{
  "tabs": [//可多个tab
    {
      "tab": {//表单标签页
        "title": "基础信息",//标签页标题
      },
      "config": [//表单字段配置
        {//每组为一行
          "columns": [`column`],//每行中的列，列为column类型
          "uid": "6NRPLAflL3",//分组每行的唯一标识
        },
		...//多行
	  ]
	},
  ]
}
```

菜单页面中列表和表单展示的字段配置，有值则传参，没有值不需要传递该参数

定义为 column

| 字段名称      | 类型               | 默认值 | 描述                                                                                              |
| :------------ | :----------------- | :----- | :------------------------------------------------------------------------------------------------ |
| uid           | string             | -      | 当前项的唯一id，不填写后端接口自动生成                                                            |
| key           | string \| string[] | -      | 模型字段名称，如果是数组表示关联中的字段，支持多层级                                              |
| type          | string             | ''     | 组件类型,不传该值会默认使用模型中选择的form_type                                                  |
| props         | object             | {}     | 当前字段的其它配置信息                                                                            |
| can_search    | array              | []     | 当前字段是否可以检索，[1] 表示true 空，不传或[]表示false 仅列表 `table_config`                    |
| hide_in_table | array              | []     | 当前字段是否在表格中隐藏，[1] 表示true 空，不传或[]表示false 仅列表 `table_config`                |
| table_menu    | array              | []     | 是否开启table中的tab切换筛选，[1] 表示true 空，不传或[]表示false 仅列表 `table_config`            |
| sort          | array              | []     | 当前字段是否开启排序，[1] 表示true 空，不传或[]表示false 仅列表 `table_config`                    |
| editable      | bool               | false  | 当前字段是否开启编辑 仅列表 `table_config`                                                        |
| editable_type | string             | number | 当前字段开启编辑的输入框类型 支持 number 和 string 即数字输入框和文本输入框 仅列表 `table_config` |
| readonly      | bool               | false  | 是否只读 仅表单 `form_config`                                                                     |
| required      | bool               | false  | 是否必填 仅表单 `form_config`                                                                     |
| hidden        | bool               | false  | 是否隐藏 仅表单 `form_config`                                                                     |
| disabled      | bool               | false  | 是否禁用 仅表单 `form_config`                                                                     |

其中，`props` 对象可以包含以下属性：

| 参数名        | 类型   | 描述                                                                                                                             |
| :------------ | :----- | :------------------------------------------------------------------------------------------------------------------------------- |
| title         | string | 自定义表头title或form项label                                                                                                     |
| dataIndex     | string | 自定义数据的key，数据接口返回新增自定义的键值等                                                                                  |
| width         | string | 列宽 px                                                                                                                          |
| span          | number | 列的栅格 共24格                                                                                                                  |
| tip           | object | `{placeholder,tooltip,extra}` 表单 placeholder提示，tooltip问好hover提示，组件后追加 extra提示                                   |
| fieldProps    | object | antdesign pro components的表单项配置                                                                                             |
| if            | string | 当前项是否显示的条件格式位 {{record?.show}} 双括号包裹表达式，返回真则显示否则隐藏，record表示当前数据信息，user表示当前用户信息 |
| dom_direction | string | 自定义dom列的排列方式有 `horizontal` `vertical` `dropdown` `none`                                                                |
| copyable      | bool   | 是否可复制，显示复制按钮                                                                                                         |
| ellipsis      | bool   | 是否缩略显示长文本                                                                                                               |
| fixed         | string | 列表列固定位置有 `none` `left` `right`                                                                                           |

其中 `type`表单类型有：

```json
[
	{ "label": "日期 - date", "value": "date" },
	{ "label": "日期区间 - dateRange", "value": "dateRange" },
	{ "label": "日期时间 - dateTime", "value": "dateTime" },
	{ "label": "时间区间 - dateTimeRange", "value": "dateTimeRange" },
	{ "label": "上传 - uploader", "value": "uploader" },
	{ "label": "自定义显示 - customerColumn", "value": "customerColumn" },
	{ "label": "密码 - password", "value": "password" },
	{ "label": "头像显示 - avatar", "value": "avatar" },
	{ "label": "导出 - export", "value": "export" },
	{ "label": "导入 - import", "value": "import" },
	{ "label": "头部操作栏 - toolbar", "value": "toolbar" },
	{ "label": "底部选择操作栏 - selectbar", "value": "selectbar" },
	{ "label": "省市区 - pca", "value": "pca" },
	{ "label": "用户权限 - userPerm", "value": "userPerm" },
	{ "label": "html显示", "value": "html" },
	{ "label": "Select选择器", "value": "select" },
	{ "label": "拾色器 - colorPicker", "value": "colorPicker" },
	{ "label": "下拉操作 - dropdownAction", "value": "dropdownAction" },
	{ "label": "异步下拉选择器 - debounceSelect", "value": "debounceSelect" },
	{ "label": "搜索select", "value": "searchSelect" },
	{ "label": "评分 - rate", "value": "rate" },
	{ "label": "滑动条 - slider", "value": "saSlider" },
	{ "label": "操作栏 - option", "value": "option" },
	{ "label": "排序 - dragsort", "value": "dragsort" },
	{ "label": "日期年 - dateYear", "value": "dateYear" },
	{ "label": "日期季度 - dateQuarter", "value": "dateQuarter" },
	{ "label": "日期月 - dateMonth", "value": "dateMonth" },
	{ "label": "日期周 - dateWeek", "value": "dateWeek" },
	{ "label": "月份区间 - dateMonthRange", "value": "dateMonthRange" },
	{ "label": "周区间 - dateWeekRange", "value": "dateWeekRange" },
	{ "label": "年区间 - dateYearRange", "value": "dateYearRange" },
	{ "label": "季度区间 - dateQuarterRange", "value": "dateQuarterRange" },
	{ "label": "时间 - time", "value": "time" },
	{ "label": "编辑表格 - saFormTable", "value": "saFormTable" },
	{ "label": "属性配置 - jsonForm", "value": "jsonForm" },
	{ "label": "多行编辑 - formList", "value": "formList" },
	{ "label": "多行编辑 - saFormList", "value": "saFormList" },
	{ "label": "json编辑器 - jsonEditor", "value": "jsonEditor" },
	{ "label": "jsonCode", "value": "jsonCode" },
	{ "label": "弹层选择器 - modalSelect", "value": "modalSelect" },
	{ "label": "富文本 - tinyMCE", "value": "tinyEditor" },
	{ "label": "规格编辑 - guigePanel", "value": "guigePanel" },
	{ "label": "权限配置 - permGroup", "value": "permGroup" },
	{ "label": "自定义 - cdependency", "value": "cdependency" },
	{ "label": "微信自定义菜单 - wxMenu", "value": "wxMenu" },
	{ "label": "穿梭框 - saTransfer", "value": "saTransfer" },
	{ "label": "地图选点 - mapInput", "value": "mapInput" },
	{ "label": "地图显示 - mapShow", "value": "mapShow" },
	{ "label": "文本域 - textarea", "value": "textarea" },
	{ "label": "markdown编辑器 - mdEditor", "value": "mdEditor" },
	{ "label": "分割线 - divider", "value": "divider" },
	{ "label": "数字 - digit", "value": "digit" },
	{ "label": "icon选择器 - iconSelect", "value": "iconSelect" },
	{ "label": "treeSelect", "value": "treeSelect" },
	{ "label": "cascader", "value": "cascader" },
	{ "label": "radio", "value": "radio" },
	{ "label": "checkbox", "value": "checkbox" },
	{ "label": "switch", "value": "switch" },
	{ "label": "AutoComplete", "value": "saAutoComplete" },
	{ "label": "单选按钮 - radioButton", "value": "radioButton" },
	{ "label": "日期时间 - datetime", "value": "datetime" },
	{ "label": "日历表单 - formCalendar", "value": "formCalendar" },
	{ "label": "alert提醒 - alert", "value": "alert" }
]
```

### 3.1 获取菜单列表

- **接口描述** : 获取后台菜单列表。
- **请求路径**: `/dev/menu`
- **请求方法**: `GET`

#### 3.1.1 响应参数

| 参数名     | 类型  | 描述               |
| :--------- | :---- | :----------------- |
| data       | Array | 菜单列表数据       |
| └─chilren  | Array | 子菜单列表         |
| └─其它字段 | Any   | 菜单的其它字段信息 |

#### 3.1.2 请求示例

```bash
curl -X GET "http://localhost:8000/sadmin/dev/menu" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
```

#### 3.1.3 成功响应示例

**HTTP Status**: `200 OK`

```json
{
	"code": 0,
	"message": "success",
	"data": [
		{
			//菜单的字段信息
		}
	]
}
```

### 3.2 获取菜单详情

- **接口描述** : 获取后台菜单列表。
- **请求路径**: `/dev/menu/show`
- **请求方法**: `GET`

#### 3.2.1 请求参数

| 参数名 | 类型    | 必填 | 描述                      | 默认值 |
| :----- | :------ | :--- | :------------------------ | :----- |
| id     | Integer | 否   | 菜单ID，0的话表示新增菜单 | `0`    |

#### 3.2.1 响应参数

| 参数名 | 类型   | 描述         |
| :----- | :----- | :----------- |
| data   | Object | 模型信息数据 |

#### 3.2.2 请求示例

```bash
curl -X GET "http://localhost:8000/sadmin/dev/menu/show?id=1" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
```

#### 3.2.3 成功响应示例

**HTTP Status**: `200 OK`

```json
{
	"code": 0,
	"message": "success",
	"data": {
		//菜单的字段信息
	}
}
```

### 3.3 提交保存菜单

- **接口描述** : 提交保存菜单。
- **请求路径**: `/dev/menu`
- **请求方法**: `POST`

#### 3.3.1 请求参数 (Body)

| 参数名 | 类型   | 必填 | 描述     | 示例值                                    |
| :----- | :----- | :--- | :------- | :---------------------------------------- |
| base   | Object | 是   | 菜单字段 | `{id:1, title:'菜单名称',path:'news'...}` |

#### 3.3.2 响应参数

| 参数名 | 类型   | 描述                   |
| :----- | :----- | :--------------------- |
| data   | Object | 修改后或新增后菜单信息 |

#### 3.3.3 请求示例

```bash
curl -X POST "http://localhost:8000/sadmin/dev/menu" \
  -H "Content-Type: application/json" \
  -d '{
    "base": {
        "id": 1,
		"title": "菜单名称",
		"path": "news"
    }
  }'
```

### 3.4 表格列表列操作

- **接口描述** : 表格列表列的操作包括新增、修改。
- **请求路径**: `/dev/menu/editTableColumn`
- **请求方法**: `POST`

#### 3.4.1 请求参数 (Body)

| 参数名       | 类型               | 必填 | 描述                                        | 示例值                         |
| :----------- | :----------------- | :--- | :------------------------------------------ | :----------------------------- |
| base         | Object             | 是   | 提交的字段信息                              | {}                             |
| └ id         | int                | 是   | 菜单id                                      | 1                              |
| └ actionType | string             | 是   | `add` 新增列 默认 `edit`编辑                | edit add                       |
| └ key        | string \| string[] | 是   | 模型字段                                    | 'desc' 或者 ['desc']           |
| └ uid        | string             | 是   | 新增时表示在该列后新增，编辑时为编辑列的uid | 2TuijwKcRP                     |
| └ 其它字段   | any                | 否   | 菜单项column中的其它字段                    | `{props:{title:"自定义标题"}}` |

#### 3.4.2 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | string | 操作结果 |

### 3.5 表格列表列排序

- **接口描述** : 表格列表列的排序。
- **请求路径**: `/dev/menu/sortTableColumns`
- **请求方法**: `POST`

#### 3.5.1 请求参数 (Body)

| 参数名  | 类型  | 必填 | 描述                                | 示例值                       |
| :------ | :---- | :--- | :---------------------------------- | :--------------------------- |
| id      | int   | 是   | 菜单id                              | 1                            |
| columns | array | 是   | ['当前列的uid','移动到目标列的uid'] | ["RxE3WSkqjQ", "uchmvgsWeY"] |

#### 3.5.2 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | string | 操作结果 |

### 3.6 表格列表列删除

- **接口描述** : 表格列表列的排序。
- **请求路径**: `/dev/menu/deleteTableColumn`
- **请求方法**: `POST`

#### 3.6.1 请求参数 (Body)

| 参数名 | 类型   | 必填 | 描述           | 示例值     |
| :----- | :----- | :--- | :------------- | :--------- |
| base   | Object | 是   | 提交的字段信息 |            |
| └ id   | int    | 是   | 菜单id         | 1          |
| └ uid  | string | 是   | 删除列的uid    | 2TuijwKcRP |

#### 3.6.2 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | string | 操作结果 |

### 3.7 表单列操作

- **接口描述** : 表单列的操作包括新增、修改。
- **请求路径**: `/dev/menu/editFormColumn`
- **请求方法**: `POST`

#### 3.7.1 请求参数 (Body)

| 参数名       | 类型               | 必填 | 描述                                        | 示例值                         |
| :----------- | :----------------- | :--- | :------------------------------------------ | :----------------------------- |
| base         | Object             | 是   | 提交的字段信息                              | {}                             |
| └ id         | int                | 是   | 菜单id                                      | 1                              |
| └ actionType | string             | 是   | `add` 新增列 默认 `edit`编辑                | edit add                       |
| └ key        | string \| string[] | 是   | 模型字段                                    | 'desc' 或者 ['desc']           |
| └ uid        | string             | 是   | 新增时表示在该列后新增，编辑时为编辑列的uid | 2TuijwKcRP                     |
| └ 其它字段   | any                | 否   | 菜单项column中的其它字段                    | `{props:{title:"自定义标题"}}` |

#### 3.7.2 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | string | 操作结果 |

### 3.8 表单列排序

- **接口描述** : 表格列表列的排序。
- **请求路径**: `/dev/menu/sortFormColumns`
- **请求方法**: `POST`

#### 3.8.1 请求参数 (Body)

| 参数名  | 类型  | 必填 | 描述                                        | 示例值                       |
| :------ | :---- | :--- | :------------------------------------------ | :--------------------------- |
| id      | int   | 是   | 菜单id                                      | 1                            |
| columns | array | 是   | ['当前列的uid','移动到目标列或目标组的uid'] | ["RxE3WSkqjQ", "uchmvgsWeY"] |

#### 3.8.2 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | string | 操作结果 |

### 3.9 表单列列删除

- **接口描述** : 表格列表列的排序。
- **请求路径**: `/dev/menu/deleteFormColumn`
- **请求方法**: `POST`

#### 3.9.1 请求参数 (Body)

| 参数名 | 类型   | 必填 | 描述           | 示例值     |
| :----- | :----- | :--- | :------------- | :--------- |
| base   | Object | 是   | 提交的字段信息 |            |
| └ id   | int    | 是   | 菜单id         | 1          |
| └ uid  | string | 是   | 删除列的uid    | 2TuijwKcRP |

#### 3.9.2 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | string | 操作结果 |

### 3.10 菜单删除

- **接口描述** : 表格列表列的排序。
- **请求路径**: `/dev/menu/1`
- **请求方法**: `DELETE`

#### 3.10.1 请求参数 (Body)

| 参数名 | 类型 | 必填 | 描述   | 示例值 |
| :----- | :--- | :--- | :----- | :----- |
| id     | int  | 是   | 菜单id | 1      |

#### 3.10.2 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | string | 操作结果 |

---

## 4.模型相关

### 4.0 模型字段说明

| 字段名称       | 类型         | 默认值         | 描述                                      |
| :------------- | :----------- | :------------- | :---------------------------------------- |
| id             | int          | AUTO_INCREMENT | id                                        |
| title          | varchar(255) | ''             | 标题                                      |
| name           | varchar(255) | ''             | 名称                                      |
| type           | int          | 0              | 0-文件夹, 1-模型                          |
| columns        | text         | NULL           | 模型的表字段信息                          |
| created_at     | datetime     | NULL           | 创建时间                                  |
| updated_at     | datetime     | NULL           | 更新时间                                  |
| parent_id      | int          | 0              | 上级id                                    |
| displayorder   | int          | 0              | 显示排序                                  |
| leixing        | varchar(255) | ''             | 模型类型 (category \| normal)             |
| admin_type     | varchar(255) | ''             | system - 系统模型 \| other - 所属项目模型 |
| search_columns | text         | NULL           | 字段搜索配置                              |
| unique_fields  | varchar(255) | ''             | 模型的唯一索引字段                        |
| setting        | text         | NULL           | 其它设置都放这                            |

#### 4.0.1 columns 字段配置

| 字段名称             | 字段名 (dataIndex) | 描述                                                                                   | 是否必填 |
| -------------------- | ------------------ | -------------------------------------------------------------------------------------- | -------- |
| 可为空               | empty              | `[1]` checkbox传递 数组表示true                                                        | 否       |
| 名称                 | title              | 字段名称                                                                               | 是       |
| 字段                 | name               | 字段名                                                                                 | 是       |
| 类型                 | type               | 数据库表字段类型值，如果选择枚举类型，需要在字段配置中配置json可选数据，第一个为默认值 | 是       |
| 默认值               | default            | 字段默认值                                                                             | 否       |
| 长度                 | length             | 字段长度 当时varchar或char时需要传输                                                   | 否       |
| 备注                 | desc               | 字段描述                                                                               | 否       |
| form类型             | form_type          | 字段表单自动类型                                                                       | 否       |
| 配置                 | setting            | -                                                                                      | 否       |
| └ 图片或视频数量限制 | image_count        | 上传图片类型设置图片数量限制                                                           | 否       |
| └ 省市区层级         | pca_level          | 省市区层级设置 1,2,3分成3层                                                            | 否       |
| └ 省市区前缀         | pca_topCode        | 限定上级省市显示，逗号分割                                                             | 否       |
| └ label              | label              | 选择器label 默认title                                                                  | 否       |
| └ value              | value              | 选择器value 默认id                                                                     | 否       |
| └ children           | children           | 选择器children 默认children                                                            | 否       |
| └ swtich开启         | open               | 开关类型打开的描述语                                                                   | 否       |
| └ swtich关闭         | close              | 开关类型关闭的描述语                                                                   | 否       |
| └ 多语言             | locale             | 是否开启多语言 true false                                                              | 否       |
| └ 图片裁切           | image_crop         | 图片上传是否开启裁剪 true false                                                        | 否       |
| └ json可选数据       | json               | 提供json选择数据 [{label,value,color}]                                                 | 否       |
| 菜单                 | table_menu         | 列表中时否开启tab菜单 true false                                                       | 否       |

其中`form_type`的值有：取value值,默认位空值

```json
[
	{ "label": "搜索cascader", "value": "search_select" },
	{ "label": "搜索select - 单选", "value": "searchSelect" },
	{ "label": "搜索selects - 多选", "value": "searchSelects" },
	{ "label": "下拉选择- select", "value": "select" },
	{ "label": "单选按钮 - radioButton", "value": "radioButton" },
	{ "label": "下拉多选 -selects", "value": "selects" },
	{ "label": "checkbox - checkbox", "value": "checkbox" },
	{ "label": "图片上传 - image", "value": "image" },
	{ "label": "文件上传 - file", "value": "file" },
	{ "label": "阿里云视频上传 - aliyunVideo", "value": "aliyunVideo" },
	{ "label": "密码 - password", "value": "password" },
	{ "label": "文本域 - textarea", "value": "textarea" },
	{ "label": "开关 - switch", "value": "switch" },
	{ "label": "时间 - datetime", "value": "datetime" },
	{ "label": "日期 - date", "value": "date" },
	{ "label": "Time - time", "value": "time" },
	{ "label": "层级多选 - cascaders", "value": "cascaders" },
	{ "label": "层级选择 - cascader", "value": "cascader" },
	{ "label": "省市区 - pca", "value": "pca" },
	{ "label": "地图选点 - mapInput", "value": "mapInput" },
	{ "label": "富文本 - tinyMCE", "value": "tinyEditor" },
	{ "label": "价格2- price", "value": "price" },
	{ "label": "价格3- mprice", "value": "mprice" },
	{ "label": "价格4- mmprice", "value": "mmprice" },
	{ "label": "数字- digit", "value": "digit" },
	{ "label": "json", "value": "json" },
	{ "label": "单选弹层 - modalSelect", "value": "modalSelect" },
	{ "label": "多选弹层 - modalSelects", "value": "modalSelects" },
	{ "label": "拾色器 - colorPicker", "value": "colorPicker" },
	{ "label": "图标选择器 - iconSelect", "value": "iconSelect" },
	{ "label": "markdown编辑器 - mdEditor", "value": "mdEditor" },
	{ "label": "滑动条 - slider", "value": "saSlider" },
	{ "label": "页面配置 - Config", "value": "config" }
]
```

### 4.1 获取模型列表

- **接口描述** : 获取模型列表。
- **请求路径**: `/dev/model`
- **请求方法**: `GET`

#### 4.1.1 响应参数

| 参数名     | 类型  | 描述               |
| :--------- | :---- | :----------------- |
| data       | Array | 模型列表数据       |
| └─chilren  | Array | 子模型列表         |
| └─其它字段 | Any   | 模型的其它字段信息 |

#### 4.1.2 请求示例

```bash
curl -X GET "http://localhost:8000/sadmin/dev/model" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
```

#### 4.1.3 成功响应示例

**HTTP Status**: `200 OK`

```json
{
	"code": 0,
	"message": "success",
	"data": [
		{
			//模型的字段信息
		}
	]
}
```

### 4.2 获取模型详情

- **接口描述** : 获取后台菜单列表。
- **请求路径**: `/dev/model/show`
- **请求方法**: `GET`

#### 4.2.1 请求参数

| 参数名 | 类型    | 必填 | 描述                      | 默认值 |
| :----- | :------ | :--- | :------------------------ | :----- |
| id     | Integer | 否   | 模型ID，0的话表示新增模型 | `0`    |

#### 4.2.1 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | Object | 模型信息 |

#### 4.2.2 请求示例

```bash
curl -X GET "http://localhost:8000/sadmin/dev/model/show?id=1" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
```

#### 4.2.3 成功响应示例

**HTTP Status**: `200 OK`

```json
{
	"code": 0,
	"message": "success",
	"data": {
		//模型的字段信息
	}
}
```

### 4.3 提交保存模型

- **接口描述** : 提交保存模型。
- **请求路径**: `/dev/model`
- **请求方法**: `POST`

#### 4.3.1 请求参数 (Body)

| 参数名 | 类型   | 必填 | 描述     | 示例值                                    |
| :----- | :----- | :--- | :------- | :---------------------------------------- |
| base   | Object | 是   | 模型字段 | `{id:1, title:'模型名称',name:'news'...}` |

#### 4.3.2 响应参数

| 参数名 | 类型   | 描述                   |
| :----- | :----- | :--------------------- |
| data   | Object | 修改后或新增后菜单信息 |

#### 4.3.3 请求示例

```bash
curl -X POST "http://localhost:8000/sadmin/dev/model" \
  -H "Content-Type: application/json" \
  -d '{
    "base": {
        "id": 1,
		"title": "模型名称",
		"name": "news"
    }
  }'
```

### 4.4 格式化模型文件

- **接口描述** : 提交保存模型。
- **请求路径**: `/dev/formatFile\{id}`
- **请求方法**: `GET`

#### 4.4.1 请求参数 (GET)

| 参数名 | 类型 | 必填 | 描述   | 示例值 |
| :----- | :--- | :--- | :----- | :----- |
| id     | int  | 是   | 模型ID | 1      |

#### 4.4.2 响应参数

| 参数名 | 类型   | 描述                   |
| :----- | :----- | :--------------------- |
| data   | Object | 修改后或新增后菜单信息 |

## 5.关联相关

### 5.0 关联字段说明

| 字段名称               | 类型         | 默认值         | 描述                                                                   |
| :--------------------- | :----------- | :------------- | :--------------------------------------------------------------------- |
| id                     | int          | AUTO_INCREMENT | 主键ID                                                                 |
| model_id               | int          | 0              | 所属模型id                                                             |
| title                  | varchar(255) | ''             | 关系名称                                                               |
| name                   | varchar(255) | ''             | 模型关系名称                                                           |
| type                   | varchar(255) | ''             | 关系类型 (one \| many)                                                 |
| foreign_model_id       | int          | 0              | 关联模型id                                                             |
| foreign_key            | varchar(255) | ''             | 关联模型字段名称                                                       |
| local_key              | varchar(255) | ''             | 当前模型的字段名称                                                     |
| created_at             | datetime     | NULL           | 创建时间                                                               |
| updated_at             | datetime     | NULL           | 更新时间                                                               |
| can_search             | int          | 0              | 1-支持搜索                                                             |
| search_columns         | varchar(255) | ''             | 搜索哪些字段 英文逗号拼接                                              |
| show_column_name       | varchar(255) | ''             | 关联模型展示信息的字段名称 英文逗号拼接                                |
| with_count             | int          | 0              | 是否计算数量                                                           |
| with_sum               | varchar(255) | ''             | hasmany类型时存储需要计算和的字段 英文逗号拼接                         |
| is_with                | int          | 1              | 该关联是否加入with数组                                                 |
| select_columns         | varchar(500) | ''             | 关联模型包含字段 (可以包含多级) 英文逗号拼接                           |
| is_with_in_page        | int          | NULL           | 该关联是否加入页面级的with (数据会设置到columnData中供组件使用context) |
| in_page_select_columns | varchar(255) | ''             | 关联包含的字段 (只包含一级) 英文逗号拼接                               |
| with_default           | varchar(500) | ''             | 关联的默认返回数据 (withDefault)                                       |
| filter                 | varchar(500) | ''             | 关联模型数据筛选条件 json [[id,>,10]]                                  |
| order_by               | varchar(500) | ''             | 关联数据排序方式 json [[id,desc]]                                      |
| setting                | text         | NULL           | 其它设置放在这里，之后不再新增字段                                     |

---

### 5.1 获取关联列表

- **接口描述** : 获取模型列表。
- **请求路径**: `/dev/relation`
- **请求方法**: `GET`

#### 5.1.1 请求参数

| 参数名   | 类型    | 必填 | 描述         | 默认值 |
| :------- | :------ | :--- | :----------- | :----- |
| model_id | Integer | 是   | 查询模型的id | `0`    |

#### 5.1.1 响应参数

| 参数名 | 类型  | 描述     |
| :----- | :---- | :------- |
| data   | Array | 关联数据 |

#### 5.1.2 请求示例

```bash
curl -X GET "http://localhost:8000/sadmin/dev/relation?model_id=1" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
```

#### 5.1.3 成功响应示例

**HTTP Status**: `200 OK`

```json
{
	"code": 0,
	"message": "success",
	"data": [
		{
			//关联的字段信息
		}
	]
}
```

### 5.2 获取关联详情

- **接口描述** : 获取后台菜单列表。
- **请求路径**: `/dev/relation/show`
- **请求方法**: `GET`

#### 5.2.1 请求参数

| 参数名 | 类型    | 必填 | 描述                      | 默认值 |
| :----- | :------ | :--- | :------------------------ | :----- |
| id     | Integer | 否   | 关联ID，0的话表示新增模型 | `0`    |

#### 5.2.1 响应参数

| 参数名 | 类型   | 描述     |
| :----- | :----- | :------- |
| data   | Object | 关联信息 |

#### 5.2.2 请求示例

```bash
curl -X GET "http://localhost:8000/sadmin/dev/relation/show?id=1" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
```

#### 5.2.3 成功响应示例

**HTTP Status**: `200 OK`

```json
{
	"code": 0,
	"message": "success",
	"data": {
		//关联的字段信息
	}
}
```

### 5.3 提交保存关联

- **接口描述** : 提交保存关联。
- **请求路径**: `/dev/relation`
- **请求方法**: `POST`

#### 5.3.1 请求参数 (Body)

| 参数名 | 类型   | 必填 | 描述     | 示例值                                                                                                                   |
| :----- | :----- | :--- | :------- | :----------------------------------------------------------------------------------------------------------------------- |
| base   | Object | 是   | 模型字段 | `{id:1, title:'分类',name:'category',model_id:1000,foreign_model_id:1001,type:one,local_key:category_id,foreign_key:id}` |

#### 5.3.2 响应参数

| 参数名 | 类型   | 描述                   |
| :----- | :----- | :--------------------- |
| data   | Object | 修改后或新增后关联信息 |

#### 5.3.3 请求示例

```bash
curl -X POST "http://localhost:8000/sadmin/dev/relation" \
  -H "Content-Type: application/json" \
  -d '{
    "base": {
        "id": 1,
		"title": "分类",
		"name": "category",
		"model_id": 1000,
		"foreign_model_id": 1001,
		"type": "one",
		"local_key": "category_id",
		"foreign_key": "id"
    }
  }'
```
