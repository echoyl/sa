import { modal } from '@/components/Sadmin/message';
import { ExclamationCircleFilled } from '@ant-design/icons';
import { Button } from 'antd';

export default function App() {
  const showConfirm = () => {
    modal.confirm({
      title: 'Do you Want to delete these items?',
      icon: <ExclamationCircleFilled />,
      content: 'Some descriptions',
      onOk() {
        console.log('OK');
      },
      onCancel() {
        console.log('Cancel');
      },
    });
  };
  return <Button onClick={showConfirm}>Confirm</Button>;
}
