import { saFormColumnsType } from '@/components/Sadmin/helpers';

const pieColumns = (data): saFormColumnsType => [
  {
    valueType: 'group',
    columns: [
      {
        title: '角度映射字段',
        dataIndex: ['defaultConfig', 'angleField'],
        colProps: { span: 6 },
        valueType: 'select',
        fieldProps: {
          options: data?.fields,
        },
      },
      {
        title: '颜色映射字段',
        dataIndex: ['defaultConfig', 'colorField'],
        colProps: { span: 6 },
        valueType: 'select',
        fieldProps: {
          options: data?.fields,
        },
      },
      {
        title: '饼图半径',
        dataIndex: ['defaultConfig', 'radius'],
        valueType: 'digit',
        colProps: { span: 6 },
        width: '100%',
        fieldProps: {
          min: 0.1,
          max: 1,
          step: 0.1,
          defaultValue: 0.9,
        },
      },
      {
        title: '饼图内半径',
        dataIndex: ['defaultConfig', 'innerRadius'],
        valueType: 'digit',
        colProps: { span: 6 },
        width: '100%',
        fieldProps: {
          min: 0.1,
          max: 1,
          step: 0.1,
          defaultValue: 0.6,
        },
      },
    ],
  },
];

export default pieColumns;
