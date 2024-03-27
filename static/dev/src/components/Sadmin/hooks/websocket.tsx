import { adminTokenName } from '@/services/ant-design-pro/sadmin';
import { App, message, Modal, notification } from 'antd';
import React, { useContext, useEffect, useState } from 'react';
import { SaDevContext } from '../dev';
import { isJsonString } from '../helpers';

import FormFromBread from '../formFromBread';
import ConfirmForm from '../action/confirmForm';
import ButtonModal from '../action/buttonModal';
import TableFromBread from '../tableFromBread';

// 创建WebSocket上下文
export const WebSocketContext = React.createContext<{
  socket?: WebSocket;
  bind?: (socket?: WebSocket) => void;
}>({});

// 高阶组件，用于在所有子组件中提供WebSocket实例
// const withWebSocket = Comp => props => {
//   const ws = useWebSocket();
//   return <Comp {...props} webSocket={ws} />;
// };

// 自定义钩子，用于管理WebSocket连接
const useWebSocket = () => {
  const [socket, setSocket] = useState<WebSocket | null>(null);
  const { setting } = useContext(SaDevContext);
  let timeinterval: any = null;
  const bind = (ws?: WebSocket) => {
    const token = localStorage.getItem(adminTokenName);
    if (!token) {
      //未登录不用绑定
      console.log('no token');
      return;
    }
    if (ws) {
      ws.send(JSON.stringify({ type: 'bind', data: { token } }));
    } else {
      socket?.send(JSON.stringify({ type: 'bind', data: { token } }));
    }

    console.log('send bind', token);
    return;
  };
  useEffect(() => {
    const url = setting.socket?.url;
    const ws = new WebSocket(url);
    setSocket(ws);

    ws.onopen = (e) => {
      bind(ws);
      if (setting.socket?.ping) {
        timeinterval = setInterval(
          () => {
            ws.send(setting.socket?.pingData);
          },
          parseInt(setting.socket?.pingInterval) * 1000,
        );
      }
    };
    ws.onclose = (e) => {
      if (timeinterval) {
        clearInterval(timeinterval);
      }
      console.log('close', e);
    };

    return () => ws.close(); // 组件卸载时关闭连接
  }, []);

  return { socket, bind };
};

// 使用WebSocketContext提供WebSocket实例
const WebSocketProvider = (props) => {
  const { socket, bind } = useWebSocket();
  const [modalFormOpen, setModalFormOpen] = useState(false);
  const [modalTableOpen, setModalTableOpen] = useState(false);
  const [messageData, setMessageData] = useState<{ [key: string]: any }>({});
  const [messageApi, contextHolder] = message.useMessage();
  const [notificationApi, contextHolderNotification] = notification.useNotification();
  type NotificationType = 'success' | 'info' | 'warning' | 'error';
  useEffect(() => {
    if (socket) {
      socket.onmessage = (e) => {
        if (isJsonString(e.data)) {
          const data = JSON.parse(e.data);
          const { type } = data;
          setMessageData(data.data);
          if (type == 'modalForm') {
            setModalFormOpen(true);
          } else if (type == 'modalTable') {
            setModalTableOpen(true);
          } else if (type == 'message' && data.data?.message) {
            messageApi.open(data.data?.message);
          } else if (type == 'notification' && data.data?.notification) {
            notificationApi[data.data?.notification.type as NotificationType]?.({
              ...data.data?.notification,
              description: (
                <div dangerouslySetInnerHTML={{ __html: data.data.notification.description }}></div>
              ),
            });
          }
        }
      };
    }
  }, [socket]);
  return (
    <WebSocketContext.Provider value={{ socket, bind }}>
      <>
        {props.children}
        {contextHolder}
        {contextHolderNotification}
        {messageData?.modalForm && (
          <ConfirmForm
            trigger={<></>}
            open={modalFormOpen}
            onOpen={(open) => {
              setModalFormOpen(open);
            }}
            page={messageData?.modalForm?.page}
            msg={messageData?.modalForm?.title}
            key="modal"
          />
        )}
        {messageData?.modalTable && (
          <ButtonModal
            open={modalTableOpen}
            afterOpenChange={(open) => {
              setModalTableOpen(open);
            }}
            key="tablemodal"
            trigger={<></>}
            title={messageData?.modalTable?.title}
            modalProps={{ footer: null }}
          >
            <TableFromBread
              fieldProps={{ path: messageData?.modalTable?.page }}
              record={{}}
              alwaysenable={true}
            />
          </ButtonModal>
        )}
      </>
    </WebSocketContext.Provider>
  );
};

// 使用WebSocketContext消费WebSocket实例
export const useWs = () => {
  const context = useContext(WebSocketContext);
  if (!context) {
    throw new Error('useWs must be used within a WebSocketProvider');
  }
  return context;
};

export default WebSocketProvider;
