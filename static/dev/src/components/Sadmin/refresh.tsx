import { currentUser } from '@/services/ant-design-pro/sadmin';
import { SyncOutlined } from '@ant-design/icons';
import { useModel } from '@umijs/max';
import { message, Space } from 'antd';
import { uid } from './helpers';
export default () => {
  const { setInitialState } = useModel('@@initialState');
  const [messageApi, contextHolder] = message.useMessage();
  const reload = async () => {
    const msg = await currentUser();
    //const msg = await cuser();

    setInitialState((s) => ({
      ...s,
      currentUser: { ...msg.data, uid: uid() },
    })).then(() => {
      messageApi.success('刷新菜单成功');
    });

    return msg.data;
  };

  return (
    <span onClick={reload} style={{ width: '100%', textAlign: 'center', display: 'inline-block' }}>
      {contextHolder}
      <Space>
        <SyncOutlined />
        刷新
      </Space>
    </span>
  );
};
