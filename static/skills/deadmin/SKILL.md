---
name: deadmin
description: 创建模型，菜单时调用API，只调用api。
---

# DeAdmin

## 何时使用此技能

当需要创建或修改后台模块，菜单，模型，菜单时使用，只根据需要调用相应api，不新增项目文件,需要修改控制器或模型文件时，代码写在标有customer注释的代码块中，如果需要修改，请自行修改。

## 使用流程

- 调用api接口前如果没有登录，则需要先调用登录接口，登录成功后返回token，后续调用接口时需要将token放在请求头中
- 根据用户命令调用对应的api接口，接口文档在`Apis.md`中

## 模型字段规则

配置模型字段是自动根据字段可能的类型选择form类型

- 开关类型使用switch，常见字段名如state，open等
- 图片上传使用image，常见字段名如image，avatar，titlepic，pics等
- 文件上传使用file，常见字段名如file，doc等
- 描述使用textarea，常见字段名如desc，description，intro等
- 富文本使用tinyEditor，常见字段名如content，detail等
- 省市区选择，需要创建province，city，area这三个字段，province选择pca类型。菜单table_config中只显示有form_type的province字段，并配置can_search。列表表头和表单label都配置为`省市区`
- 选择器默认使用select，常见字段名如category_id，group_id,role_id等，需要创建关联。只有当明确需要异步搜索功能时才使用searchSelect
- 多选选择器使用selects，常见字段名如tag_ids等，需要创建关联
- 地图选点，需要创建lat,lng两个字段类型为varchar，lat选择form_type为mapInput，菜单table_config中不要显示lat,lng字段,表单中label配置为经纬度，只显示有form_type的lat字段

## 使用方法

### 创建或修改模型

`type`的值为 `0`为模型文件夹，`1`为真实模型需要创建模型字段信息

- 自动创建上级模型文件夹或找到可能的上级模型文件夹，自动配置名称和标题
- 如果是模型文件夹则创建完成，以下步骤忽略
- 自动生成模型生成模型可能存在的字段信息，根据模型字段规则配置每个字段的form_tyoe，每个模型默认都有`{id:ID,state:状态,displayorder:排序}`3个字段都是int类型。
- 如果模型为分类，则`leixing`字段配置为 category，否则默认为 normal，分类模型默认加上`parent_id`上级id字段，无form_type
- 生成了模型配置后调用`提交保存模型`接口创建该模型
- 修改模型后重新调用`提交保存模型`接口
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
- 根据模型的字段信息自动配置table_config和form_config，列表列把模型字段都选上，表单将模型字段每行2个进行配置
- 配置生成后调用`提交保存菜单`接口创建该菜单

### 创建模块

先创建上级模型类型为文件夹和上级菜单,再自动生成模型和菜单，自动生成模块可能存在的模型和菜单，如果有关联模型，则自动创建该关联模型,不要创建重复的模型或菜单。
