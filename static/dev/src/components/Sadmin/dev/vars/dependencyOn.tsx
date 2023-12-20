import { saFormColumnsType } from '../../helpers';

export const dependencyOn = (columns: any[]): saFormColumnsType => {
  const show: saFormColumnsType = [
    {
      dataIndex: 'condition_type',
      valueType: 'radioButton',
      fieldProps: {
        defaultValue: 'all',
        options: [
          { label: '全部满足', value: 'all' },
          { label: '任一满足', value: 'one' },
        ],
      },
    },
    {
      dataIndex: 'condition',
      valueType: 'formList',
      columns: [
        {
          valueType: 'group',
          columns: [
            {
              title: '字段选择',
              valueType: 'cascader',
              dataIndex: 'name',
              width: 240,
              fieldProps: {
                options: columns,
                showSearch: true,
                changeOnSelect: true,
              },
            },
            {
              title: '值',
              dataIndex: 'value',
            },
            {
              title: '表达式',
              dataIndex: 'exp',
            },
          ],
        },
      ],
    },
  ];
  const render: saFormColumnsType = [
    {
      dataIndex: 'condition',
      valueType: 'formList',
      columns: [
        {
          valueType: 'group',
          columns: [
            {
              title: '字段选择',
              valueType: 'cascader',
              dataIndex: 'name',
              fieldProps: {
                options: columns,
                showSearch: true,
                changeOnSelect: true,
              },
              width: 'md',
            },
          ],
        },
      ],
    },
  ];

  return [
    {
      title: '类型',
      dataIndex: 'type',
      valueType: 'radioButton',
      tooltip: '控制切换是否显示，或依赖项显示可以添加计算等',
      fieldProps: {
        defaultValue: 'show',
        options: [
          { label: '切换显示', value: 'show' },
          { label: '渲染显示', value: 'render' },
        ],
      },
    },
    {
      valueType: 'dependency',
      name: ['type'],
      columns: ({ type }) => {
        return type == 'show' ? show : render;
      },
    },
  ];
};
