import { saFormColumnsType } from '@/components/Sadmin/helpers';

const areaMapColumns = (data): saFormColumnsType => [
  {
    valueType: 'group',
    columns: [
      {
        title: '字段',
        dataIndex: ['defaultConfig', 'field'],
        colProps: { span: 12 },
        valueType: 'select',
        fieldProps: {
          options: data?.fields,
        },
      },
    ],
  },
];

export default areaMapColumns;
