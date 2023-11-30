import { Layout, Typography } from 'antd';
import { Content } from 'antd/es/layout/layout';

export default () => {
  const [show1, setShow1] = useState(false);
  const [show2, setShow2] = useState(false);
  const [show3, setShow3] = useState(false);
  return (
    <Layout>
      <Content>
        <Typography.Text code style={{ width: 120 }} ellipsis={{ tooltip: 'test' }}>
          Ant Design, a design language for background applications, is refined by Ant UED Team.
        </Typography.Text>
      </Content>
    </Layout>
  );
};
