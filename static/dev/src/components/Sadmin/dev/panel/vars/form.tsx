import { saFormColumnsType } from '@/components/Sadmin/helpers';
import { columnType } from '../../table/baseFormColumns';

const formColumns = (data, dataIndex = ['defaultConfig', 'columns']): saFormColumnsType => [
  {
    valueType: 'group',
    columns: [
      {
        title: '列表字段',
        dataIndex,
        colProps: { span: 24 },
        rowProps: { gutter: 0 },
        valueType: 'formList',
        columns: [
          {
            valueType: 'group',
            columns: [
              {
                title: 'Label',
                dataIndex: 'title',
                colProps: { span: 6 },
              },
              {
                title: 'dataIndex',
                dataIndex: 'dataIndex',
                colProps: { span: 6 },
                valueType: 'select',
                fieldProps: {
                  options: data?.fields,
                },
              },
              {
                title: '组件类型',
                dataIndex: 'valueType',
                colProps: { span: 6 },
                valueType: 'select',
                fieldProps: {
                  options: columnType,
                },
              },
              {
                title: 'fieldProps',
                dataIndex: 'fieldProps',
                colProps: { span: 6 },
                valueType: 'modalJson',
              },
            ],
          },
        ],
      },
    ],
  },
];

export default formColumns;
