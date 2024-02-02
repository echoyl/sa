import { saFormColumnsType } from '@/components/Sadmin/helpers';

const cardColumns = (data): saFormColumnsType => [
  {
    valueType: 'group',
    columns: [
      {
        title: '前缀',
        dataIndex: ['defaultConfig', 'prefix'],
        colProps: { span: 12 },
      },
      {
        title: '后缀',
        dataIndex: ['defaultConfig', 'suffix'],
        colProps: { span: 12 },
      },
    ],
  },
  {
    valueType: 'group',
    columns: [
      {
        title: '链接',
        dataIndex: ['defaultConfig', 'href'],
        colProps: { span: 24 },
      },
    ],
  },
];

export default cardColumns;
