---
name: deadmin
description: 创建模型，菜单时调用API，只调用api。
---

# DeAdmin

## 何时使用此技能

当需要创建或修改后台模块，菜单，模型，菜单时使用，只根据需要调用相应api，不新增项目文件,需要修改控制器或模型文件时，代码写在标有customer注释的代码块中，如果需要修改，请自行修改。

## 使用流程

- 调用api接口前如果没有登录，则需要先调用登录接口，登录成功后返回token，后续调用接口时需要将token放在请求头中
- 如果登录触发验证码（返回 `图形验证码错误`），则清除 `storage/framework/cache/data` 目录中的缓存文件（保留 `.gitignore`）后重试
- 根据用户命令调用对应的api接口，接口文档在`Apis.md`中

## 模型字段规则

### 模型setting配置

创建或修改模型时，`setting`字段为JSON字符串，支持以下配置：

- `soft_delete`: `true` 开启软删除，自动添加 `deleted_at` 字段
- `with_platform_id`: `true` 开启Platform字段，自动添加 `platform_id` 字段

示例：`"setting":"{\"soft_delete\":true,\"with_platform_id\":true}"`

### 字段form类型规则

配置模型字段时自动根据字段可能的类型选择form类型

- 开关类型使用switch，常见字段名如state，open等
- 如果模型字段为state状态switch类型，在创建菜单时默认配置table_menu为[1]（即开启Tab菜单筛选）。注：菜单table_config中table_menu是checkbox类型，选中值为[1]；模型中字段的table_menu是switch类型，选中值为true
- 图片上传使用image，常见字段名如image，avatar，titlepic，pics等
- 文件上传使用file，常见字段名如file，doc等
- 描述使用textarea，常见字段名如desc，description，intro等
- 富文本使用tinyEditor，常见字段名如content，detail等
- 省市区选择，需要创建province，city，area这三个字段，province选择pca类型。菜单table_config中只显示有form_type的province字段，并配置can_search。列表表头和表单label都配置为`省市区`
- 选择器默认使用select，常见字段名如category_id，group_id,role_id等，需要创建关联。只有当明确需要异步搜索功能时才使用searchSelect
- 选择器字段（select类型）需要配置setting指定label，示例如下：如果关联的模型名称字段为name（非默认的title），则设置`setting: {"label": "name"}`；如果关联模型使用默认title字段则无需配置
- 多选选择器使用selects，常见字段名如tag_ids等，需要创建关联
- 地图选点，需要创建lat,lng两个字段类型为varchar，lat选择form_type为mapInput，菜单table_config中不要显示lat,lng字段,表单中label配置为经纬度，只显示有form_type的lat字段

## 使用方法

### 创建或修改模型

`type`的值为 `0`为模型文件夹，`1`为真实模型需要创建模型字段信息

- 自动创建上级模型文件夹或找到可能的上级模型文件夹，自动配置名称和标题
- **【重要】模型`name`禁止使用下划线`_`**。虽然模型上下级关系通过`parent_id`和`name`使用`_`拼接生成数据表名，但`name`中的`_`会导致生成的PHP控制器和模型类名包含下划线（如`Daily_menuController`），违反PascalCase命名规范。应使用无下划线的单词，如`dailymenu`而非`daily_menu`。其对应的数据表名为`上级名_模型名`（如`dining_dailymenu`），由系统自动拼接
- 如果是模型文件夹则创建完成，以下步骤忽略
- 自动生成模型生成模型可能存在的字段信息，根据模型字段规则配置每个字段的form_tyoe，每个模型默认都有`{id:ID,state:状态,displayorder:排序}`3个字段都是int类型。
- 如果模型为分类，则`leixing`字段配置为 category，否则默认为 normal，分类模型默认加上`parent_id`上级id字段，无form_type
- 生成了模型配置后调用`提交保存模型`接口创建该模型
- 修改模型之前先调用`获取模型详情`接口获取当前模型最新信息（包括最新的columns），基于当前最新数据进行修改，修改模型后重新调用`提交保存模型`接口
- 自动根据模型的字段信息判断是否要生成关联模型或关联已有模型，并调用`提交保存关联`接口创建两个模型的关联
- 生成完模型，创建关联后，或修改模型后调用`格式化模型文件`接口格式化生成后端php文件
- 修改模型后，如果有菜单关联该模型则调用`提交保存菜单`接口修改该菜单

### 创建或修改关联

关联字段命名规则：当前模型中存储关联ID的字段使用`关联名称_id`格式，例如 `category_id`、`tag_id`、`role_id`。

- 1.文章模型有分类关联，则 news为文章模型，category为分类模型，关联名为 category,两个关联的字段名分别为 news.caetgory_id 和 category.id,关联关系为hasOne
- 2.文章模型有评论关联，comment为评论模型，关联名为 comments,两个关联的字段名分别为 news.id 和 comment.news_id,关联关系为hasMany
- 3.文章模型有标签关联，tag为标签模型，关联名为 tags,两个关联的字段名分别为 news.tag_id 和 tag.id,关联关系为cascaders

关联配置示例

```json
{
    "title": "分类",
    "name": "category",
    "model_id": "news模型的id",
    "foreign_model_id": "category模型的id",
    "type": "one",
    "local_key": "category_id",
    "foreign_key": "id",
    "is_with":true
}
{
    "title": "评论",
    "name": "comments",
    "model_id": "news模型的id",
    "foreign_model_id": "comment模型的id",
    "type": "many",
    "local_key": "id",
    "foreign_key": "news_id",
    "is_with":false
}
{
    "title": "标签",
    "name": "tags",
    "model_id": "news模型的id",
    "foreign_model_id": "tag模型的id",
    "type": "cascaders",
    "local_key": "tag_id",
    "foreign_key": "id",
    "is_with":false
}
```

### 创建或修改菜单

- 自动判断是否要创建上级菜单
- 如果未选模型，则page_type配置，自动根据菜单名称生成title和path
- 如果选了模型，则根据模型名称生成title和path，如果模型为分类，则page_type配置为 category,否则默认为 table,open_type配置为 drawer
- **【必须】** 创建菜单时必须配置完整的`table_config`和`form_config`，不能为空
- **【注意】首次创建菜单（id=0）时服务器不会保存`table_config`和`form_config`，创建后返回的配置为空（列表仅有option列、表单为空）。必须在创建成功后立即用该id重新调用`提交保存菜单`接口进行第二次更新，才能正确写入配置**
- **【验证】创建/更新菜单后必须调用`获取菜单详情`接口验证`table_config`和`form_config`是否完整保存。如发现列表只有option列、表单为空，说明配置未保存，需重新提交补全**
- 根据模型的字段信息手动配置`table_config`和`form_config`：
  - `table_config`：模型的所有字段都选上（关联字段用`["关联名","关联模型字段"]`格式），再加上option操作列
  - `form_config`：每行2个字段为一组，content等大字段可单独一行
- `table_config`为数组，每项格式：`{"key":"字段名","props":{"title":"列标题"},"can_search":[1]}`
- `form_config`为对象，格式：`{"tabs":[{"tab":{"title":"标签页名"},"config":[{"columns":[{"key":"字段名","props":{"title":"标签"}}]}]}]}`
- 修改菜单之前先调用`获取菜单详情`接口获取当前菜单最新信息（包括最新的table_config和form_config），基于当前最新数据进行修改
- 配置生成后调用`提交保存菜单`接口创建或更新该菜单

### 创建模块

先创建上级模型类型为文件夹和上级菜单,再自动生成模型和菜单，自动生成模块可能存在的模型和菜单，如果有关联模型，则自动创建该关联模型,不要创建重复的模型或菜单。
