import { saFormColumnsType } from '../../helpers';

const tagConfig: saFormColumnsType = [
  {
    dataIndex: 'color',
    valueType: 'colorPicker',
    title: '颜色选择',
    tooltip: '使用对象数据后，该设置失效',
  },
  {
    dataIndex: 'bordered',
    valueType: 'switch',
    title: '是否有边框',
    fieldProps: {
      defaultChecked: true,
    },
  },
];

export default tagConfig;
