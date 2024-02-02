import { Button, Modal, notification } from 'antd';
import { useState } from 'react';

export default function App() {
  const [open, setOpen] = useState(false);
  const [api, contextHolder] = notification.useNotification();
  return (
    <>
      <div style={{ height: 1200, width: '100%' }}>
        <Button
          onClick={() => {
            setOpen(true);
          }}
        >
          modal
        </Button>
        <Modal
          open={open}
          onOk={() => {
            api.info({
              message: 'test',
              description: 'test desc',
            });
            setOpen(false);
          }}
          onCancel={() => {
            setOpen(false);
          }}
          afterOpenChange={(open) => {
            setOpen(open);
          }}
        ></Modal>
      </div>
      {contextHolder}
    </>
  );
}
