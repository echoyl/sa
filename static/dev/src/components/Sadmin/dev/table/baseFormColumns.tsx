import { devDefaultFields, devTabelFields } from '@/pages/dev/model';
import { TreeNodeProps } from 'antd';
import { saFormColumnsType, saTableColumnsType } from '../../helpers';
const columnType = [
  { label: '日期 - date', value: 'date' },
  { label: '日期区间 - dateRange', value: 'dateRange' },
  { label: '时间 - dateTime', value: 'dateTime' },
  { label: '时间区间 - dateTimeRange', value: 'dateTimeRange' },
  { label: '上传-uploader', value: 'uploader' },
  { label: '自定义组件 - customerColumn', value: 'customerColumn' },
  { label: '密码 - password', value: 'password' },
  { label: '头像 - avatar', value: 'avatar' },
  { label: '导出 - export', value: 'export' },
  { label: '导入 - import', value: 'import' },
  { label: '头部操作栏 - toolbar', value: 'toolbar' },
  { label: '底部选择操作栏 - selectbar', value: 'selectbar' },
  { label: 'slider', value: 'slider' },
  { label: '省市区 - pca', value: 'pca' },
  { label: '用户权限 - userPerm', value: 'userPerm' },
  { label: 'html', value: 'html' },
  { label: 'select', value: 'select' },
];

export const getModelColumnsSelect = (id: number, allModels, level = 1) => {
  const select_data = allModels?.find((v) => v.id == id);
  //console.log(foreign_model_id, allModels, select_data);
  const fields: Array<TreeNodeProps> = [...select_data?.columns].map((v) => ({
    label: v.label ? v.label : [v.title, v.name].join(' - '),
    value: v.name,
  }));
  level += 1;

  if (level > 3) {
    //3层迭代后 直接终止 防止出现无限循环
    return fields;
  }
  //关联模型
  const guanlian: Array<TreeNodeProps> = select_data?.relations?.map((v) => ({
    label: [v.title, v.name].join(' - '),
    value: v.name,
    children: getModelColumnsSelect(v.foreign_model_id, allModels, level),
  }));
  return [...fields, ...guanlian];
};

export const getModelRelationSelect = (id: number, allModels, level = 1) => {
  const select_data = allModels?.find((v) => v.id == id);
  level += 1;
  if (level > 3) {
    //3层迭代后 直接终止 防止出现无限循环
    return [];
  }
  //关联模型
  const guanlian: Array<TreeNodeProps> = select_data?.relations?.map((v) => ({
    label: [v.title, v.name].join(' - '),
    value: v.name,
    children: getModelRelationSelect(v.foreign_model_id, allModels, level),
  }));
  //console.log('guanlian', guanlian);
  return guanlian;
};

export const getModelById = (model_id: number, models: any[]) => {
  return models.find((v) => v.id == model_id);
};

export const getModelRelations = (model_id: number, dev: { [key: string]: any }): any[] => {
  //console.log('model_id', model_id);
  const { allModels } = dev;
  const model = getModelById(model_id, allModels);
  const manyRelation: any[] = [];
  const oneRelation: any[] = [];
  model?.relations
    ?.filter((v) => v.type == 'one' || v.type == 'many')
    .map((v) => {
      //读取关联模型的字段信息
      //const foreign_model_columns = JSON.parse(v.foreign_model.columns);
      // const children = foreign_model_columns.map((v) => ({
      //   label: [v.title, v.name].join(' - '),
      //   value: v.name,
      //   children:getModelColumnsSelect(v.foreign_model_id,data.allModels)
      // }));
      const children = getModelRelationSelect(v.foreign_model_id, allModels, 2);
      //console.log('my children ', children);
      if (v.type == 'many') {
        manyRelation.push({
          label: [v.title, v.name, 'many'].join(' - '),
          value: v.name,
          children,
        });
      }
      if (v.type == 'one') {
        oneRelation.push({
          label: [v.title, v.name, 'one'].join(' - '),
          value: v.name,
          children,
        });
      }
      return {
        label: [v.title, v.name, v.type == 'one' ? 'hasOne' : 'hasMany'].join(' - '),
        value: v.name,
        children: children,
      };
    });
  return [...manyRelation, ...oneRelation];
};

export const getModelColumns = (model_id: number, dev: { [key: string]: any }) => {
  const { allModels = [] } = dev;
  const model = getModelById(model_id, allModels);
  const allColumns = getModelColumnsSelect(model_id, allModels);

  //检测模型关系 提供给table列选择字段
  const foreignOptions = model?.columns?.map((v) => ({
    label: [v.title, v.name].join(' - '),
    value: v.name,
  }));
  model.relations?.forEach((v) => {
    if (v.type == 'many') {
      if (v.with_count) {
        foreignOptions.push({
          label: [v.title, 'count'].join(' - '),
          value: [v.name, 'count'].join('_'),
        });
      }
      if (v.with_sum) {
        v.with_sum.split(',').forEach((vs) => {
          foreignOptions.push({
            label: [v.title, 'sum', vs].join(' - '),
            value: [v.name, 'sum', vs].join('_'),
          });
        });
      }
    }
  });

  //检测模型搜索设置 提供给table列选择字段 20230904 可能存在重复键值导致组件错误，暂时不要这个功能
  const existColumns = [...foreignOptions, ...devDefaultFields];
  const search_columns = model.search_columns ? model.search_columns : [];
  const searchColumn = search_columns
    .filter((v) => {
      return existColumns.findIndex((val) => val.value == v.name) < 0;
    })
    .map((v) => ({
      label: [v.name, '搜索字段'].join(' - '),
      value: v.name,
    }));

  return [...allColumns, ...devDefaultFields, ...devTabelFields, ...searchColumn];
};

type devTabelFieldsProps = {
  model_id?: number;
  dev?: { allMenus: any[]; allModels: any[] };
};

export const devBaseTableColumns = (props: devTabelFieldsProps) => {
  const { model_id = 0, dev = { allMenus: [] } } = props;
  const { allMenus = [] } = dev;
  const modelColumns2: any[] = getModelColumns(model_id, dev);
  const relations = getModelRelations(model_id, dev);

  const columns: saTableColumnsType = [
    {
      dataIndex: 'key',
      title: '字段',
      width: 'sm',
      valueType: 'cascader',
      fieldProps: {
        options: modelColumns2,
        showSearch: true,
        changeOnSelect: true,
      },
    },

    {
      valueType: 'dependency',
      name: ['props'],
      columns: ({ props }: any) => {
        //console.log(props);
        //return [];
        return [
          {
            dataIndex: '',
            title: '自定义表头',
            readonly: true,
            render: () => {
              return <div style={{ width: 100 }}>{props?.title ? props.title : ' - '}</div>;
            },
          },
        ];
      },
    },
    {
      dataIndex: 'can_search',
      valueType: 'checkbox',
      title: '搜索',
      fieldProps: {
        options: [{ label: '可搜索', value: 1 }],
      },
    },
    {
      dataIndex: 'hide_in_table',
      valueType: 'checkbox',
      title: '表中隐藏',
      width: 75,
      fieldProps: {
        options: [{ label: '隐藏', value: 1 }],
      },
    },
    {
      dataIndex: 'table_menu',
      valueType: 'checkbox',
      title: '开启tab',
      width: 75,
      fieldProps: {
        options: [{ label: 'tab', value: 1 }],
      },
    },
    {
      dataIndex: 'sort',
      valueType: 'checkbox',
      title: '开启排序',
      width: 75,
      fieldProps: {
        options: [{ label: '排序', value: 1 }],
      },
    },
    {
      dataIndex: 'props',
      title: '更多',
      valueType: 'customerColumnDev',
      fieldProps: {
        relationModel: relations,
        allMenus,
      },
      width: 75,
    },
    {
      dataIndex: 'type',
      width: 220,
      valueType: 'select',
      title: '字段类型',
      fieldProps: {
        options: columnType,
        placeholder: '请选择表字段类型',
      },
    },
    {
      dataIndex: 'left_menu',
      valueType: 'checkbox',
      title: '左侧菜单',
      fieldProps: {
        options: [{ label: '左侧菜单', value: 1 }],
      },
      width: 100,
    },
    {
      valueType: 'dependency',
      name: ['left_menu'],
      columns: ({ left_menu }: any) => {
        if (left_menu && left_menu.length > 0) {
          return [
            {
              dataIndex: 'left_menu_field',
              fieldProps: {
                placeholder: '请输入左侧菜单label，value字段名称',
              },
            },
          ];
        }
        return [];
      },
    },
  ];
  return columns;
};

export const devBaseTableFormColumns = (props: devTabelFieldsProps): saFormColumnsType => {
  const { model_id = 0, dev = { allMenus: [] } } = props;
  const modelColumns2: any[] = getModelColumns(model_id, dev);

  const columns: saFormColumnsType = [
    {
      valueType: 'group',
      columns: [
        {
          dataIndex: 'key',
          title: '字段',
          width: 'md',
          valueType: 'cascader',
          fieldProps: {
            options: modelColumns2,
            showSearch: true,
            changeOnSelect: true,
          },
        },
        {
          dataIndex: 'type',
          width: 'md',
          valueType: 'select',
          title: '字段类型',
          fieldProps: {
            options: columnType,
            placeholder: '请选择表字段类型',
          },
        },
      ],
    },
    {
      valueType: 'group',
      columns: [
        {
          dataIndex: 'can_search',
          valueType: 'checkbox',
          title: '搜索',
          fieldProps: {
            options: [{ label: '可搜索', value: 1 }],
          },
        },
        {
          dataIndex: 'hide_in_table',
          valueType: 'checkbox',
          title: '表中隐藏',
          fieldProps: {
            options: [{ label: '隐藏', value: 1 }],
          },
        },
        {
          dataIndex: 'table_menu',
          valueType: 'checkbox',
          title: '开启tab',
          fieldProps: {
            options: [{ label: 'tab', value: 1 }],
          },
        },
        {
          dataIndex: 'sort',
          valueType: 'checkbox',
          title: '开启排序',
          fieldProps: {
            options: [{ label: '排序', value: 1 }],
          },
        },
      ],
    },

    {
      dataIndex: 'left_menu',
      valueType: 'checkbox',
      title: '左侧菜单',
      fieldProps: {
        options: [{ label: '左侧菜单', value: 1 }],
      },
    },
    {
      valueType: 'dependency',
      name: ['left_menu'],
      columns: ({ left_menu }: any) => {
        if (left_menu && left_menu.length > 0) {
          return [
            {
              dataIndex: 'left_menu_field',
              fieldProps: {
                placeholder: '请输入左侧菜单label，value字段名称',
              },
            },
          ];
        }
        return [];
      },
    },
  ];
  return columns;
};
