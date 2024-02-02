import { saFormColumnsType } from '@/components/Sadmin/helpers';

const lineColumns = (data): saFormColumnsType => [
  {
    valueType: 'group',
    columns: [
      {
        title: 'x轴字段',
        dataIndex: ['defaultConfig', 'xField'],
        colProps: { span: 6 },
        valueType: 'select',
        fieldProps: {
          options: data?.fields,
        },
      },
      {
        title: 'y轴字段',
        dataIndex: ['defaultConfig', 'yField'],
        colProps: { span: 6 },
        valueType: 'select',
        fieldProps: {
          options: data?.fields,
        },
      },
      {
        title: '分组字段',
        tooltip: '多条线可使用该参数',
        dataIndex: ['defaultConfig', 'seriesField'],
        colProps: { span: 6 },
        valueType: 'select',
        fieldProps: {
          options: data?.fields,
        },
      },
      {
        title: '是否平滑',
        dataIndex: ['defaultConfig', 'smooth'],
        valueType: 'switch',
        colProps: { span: 6 },
      },
    ],
  },
];

export default lineColumns;
