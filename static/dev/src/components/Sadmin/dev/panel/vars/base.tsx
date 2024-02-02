import {
  saFormColumnsType,
  saFormTabColumnsType,
  saValueTypeMapType,
} from '@/components/Sadmin/helpers';
import { CreditCardOutlined } from '@ant-design/icons';
import { Space } from 'antd';
import areaMapColumns from './areaMap';
import barColumns from './bar';
import cardColumns from './card';
import columnColumns from './column';
import formColumns from './form';
import lineColumns from './line';
import pieColumns from './pie';
import tableColumns from './table';

const configColumn: saValueTypeMapType = {
  title: '配置',
  valueType: 'jsonEditor',
  dataIndex: 'config',
};

const chartColumns = (data): saFormColumnsType => [
  {
    title: '图表类型',
    dataIndex: ['chart', 'type'],
    valueType: 'select',
    fieldProps: {
      options: [
        { label: '饼图', value: 'pie' },
        { label: '折线图', value: 'line' },
        { label: '柱形图', value: 'column' },
        { label: '条形图', value: 'bar' },
        { label: '区域地图', value: 'areaMap' },
      ],
    },
  },
  {
    name: ['chart', 'type'],
    valueType: 'dependency',
    columns: (datas) => {
      //   const type = { chart };
      //console.log('data', data);
      if (datas?.chart?.type == 'pie') {
        return pieColumns(data);
      } else if (datas?.chart?.type == 'line') {
        return lineColumns(data);
      } else if (datas?.chart?.type == 'column') {
        return columnColumns(data);
      } else if (datas?.chart?.type == 'bar') {
        return barColumns(data);
      } else if (datas?.chart?.type == 'areaMap') {
        return areaMapColumns(data);
      }
      return [];
    },
  },
];

export const baseRowColumns = (data: any[]): saFormTabColumnsType => {
  return [
    {
      title: '基础信息',
      formColumns: [
        {
          title: 'Title',
          dataIndex: 'title',
          colProps: { span: 12 },
          // fieldProps: {
          //   options: [
          //     { label: '默认分组', value: 'normal' },
          //     { label: 'tab', value: 'tab' },
          //   ],
          //   defaultValue: 'normal',
          // },
        },
      ],
    },
  ];
};

const baseFormColumns = (data: any[]): saFormTabColumnsType => {
  return [
    {
      title: '基础信息',
      formColumns: [
        {
          valueType: 'group',
          columns: [
            {
              title: '类型',
              dataIndex: 'type',
              valueType: 'select',
              fieldProps: {
                options: [
                  {
                    label: (
                      <Space>
                        <CreditCardOutlined />
                        Card
                      </Space>
                    ),
                    value: 'card',
                  },
                  { label: 'tab', value: 'tab' },
                  { label: '图表', value: 'chart' },
                  { label: '表格', value: 'table' },
                  { label: '容器', value: 'rows' },
                  { label: '查询表单', value: 'form' },
                  { label: '个人信息', value: 'user' },
                ],
              },
              colProps: { span: 24 },
            },
          ],
        },
        {
          valueType: 'group',
          columns: [
            { title: 'Title', dataIndex: 'title', colProps: { span: 12 } },
            {
              title: '数据源',
              dataIndex: 'sourceDataName',
              valueType: 'select',
              fieldProps: {
                options: data,
              },
              colProps: { span: 12 },
            },
          ],
        },

        {
          name: ['type', 'sourceDataName'],
          valueType: 'dependency',
          columns: ({ type, sourceDataName }) => {
            if (type == 'chart') {
              return chartColumns(data?.find((v) => v.value == sourceDataName));
            } else if (type == 'table') {
              return tableColumns(data?.find((v) => v.value == sourceDataName));
            } else if (type == 'card') {
              return cardColumns(data?.find((v) => v.value == sourceDataName));
            } else if (type == 'form') {
              return formColumns(data?.find((v) => v.value == sourceDataName));
            }
            return [];
          },
        },
      ],
    },
    {
      title: '配置',
      formColumns: [
        {
          valueType: 'group',
          columns: [
            {
              title: '列宽',
              dataIndex: 'customer_span',
              valueType: 'digit',
              width: '100%',
              colProps: { span: 12 },
            },
            {
              title: '自定义高度',
              dataIndex: 'height',
              valueType: 'digit',
              width: '100%',
              colProps: { span: 12 },
            },
          ],
        },
        configColumn,
      ],
    },
  ];
};
export default baseFormColumns;
