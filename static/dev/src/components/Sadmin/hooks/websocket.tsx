import { adminTokenName } from '@/services/ant-design-pro/sadmin';
import React, { useContext, useEffect, useState } from 'react';
import { SaDevContext } from '../dev';
import { isJsonString } from '../helpers';

import ConfirmForm from '../action/confirmForm';
import ButtonModal from '../action/buttonModal';
import TableFromBread from '../tableFromBread';
import { App, message, notification } from 'antd';

// 创建WebSocket上下文
export const WebSocketContext = React.createContext<{
  socket?: WebSocket;
  bind?: (socket?: WebSocket) => void;
  clientId?: string;
  messageData?: { [key: string]: any };
}>({});

// 高阶组件，用于在所有子组件中提供WebSocket实例
// const withWebSocket = Comp => props => {
//   const ws = useWebSocket();
//   return <Comp {...props} webSocket={ws} />;
// };

// 自定义钩子，用于管理WebSocket连接
const useWebSocket = () => {
  const [socket, setSocket] = useState<WebSocket>();
  const [isInit, setIsInit] = useState(false);
  const { setting } = useContext(SaDevContext);
  let timeinterval: any = null;
  let reconnectInterval: any = null;
  const bind = (ws?: WebSocket) => {
    const token = localStorage.getItem(adminTokenName);
    //console.log('send bind', token);
    if (!token) {
      //未登录不用绑定
      //console.log('no token');
      return;
    }
    if (ws) {
      ws.send(JSON.stringify({ type: 'bind', data: { token } }));
    } else {
      socket?.send(JSON.stringify({ type: 'bind', data: { token } }));
    }

    return;
  };
  const connect = (): WebSocket => {
    const url = setting.socket?.url;
    //console.log('connect url is ', url);
    const ws = new WebSocket(url);
    if (reconnectInterval) {
      //重新连接后 如果还有重连interval 需要清除
      clearInterval(reconnectInterval);
    }
    setSocket(ws);
    init(ws);
    return ws;
  };
  const init = (ws: WebSocket) => {
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
      //重新连接 每10秒 重连一次
      console.log('on close', e);
      reconnectInterval = setInterval(() => {
        console.log('reconnect now');
        connect();
      }, 10 * 1000);
    };
  };
  useEffect(() => {
    if (!setting || isInit) {
      return;
    }
    setIsInit(true);
    if (!setting.socket?.open) {
      return () => {};
    }

    const ws = connect();

    return () => ws.close(); // 组件卸载时关闭连接
  }, [setting]);

  return { socket, bind };
};

// 使用WebSocketContext提供WebSocket实例
const WebSocketProvider = (props) => {
  const { socket, bind } = useWebSocket();
  const [clientId, setClientId] = useState();
  const [messageData, setMessageData] = useState<{ [key: string]: any }>({});

  useEffect(() => {
    if (socket) {
      socket.onmessage = (e) => {
        if (isJsonString(e.data)) {
          const data = JSON.parse(e.data);
          //console.log('on message', data);
          const { type } = data;
          setMessageData(data);
          if (type == 'init') {
            setClientId(data.data);
          }
        }
      };
    }
  }, [socket]);
  return (
    <WebSocketContext.Provider value={{ socket, bind, clientId, messageData }}>
      {props.children}
    </WebSocketContext.Provider>
  );
};

export const WebSocketListen = () => {
  const { messageData } = useContext(WebSocketContext);
  const [modalFormOpen, setModalFormOpen] = useState(false);
  const [modalTableOpen, setModalTableOpen] = useState(false);
  type NotificationType = 'success' | 'info' | 'warning' | 'error';

  const [messageApi, messageHolder] = message.useMessage();
  const [notificationApi, notificationHolder] = notification.useNotification();
  useEffect(() => {
    if (messageData) {
      const { type, data } = messageData;
      if (type == 'modalForm') {
        setModalFormOpen(true);
      } else if (type == 'modalTable') {
        setModalTableOpen(true);
      } else if (type == 'message' && data?.message) {
        messageApi.open(data?.message);
      } else if (type == 'notification' && data?.notification) {
        notificationApi[data?.notification?.type as NotificationType]?.({
          ...data.data?.notification,
          description: (
            <div
              dangerouslySetInnerHTML={{ __html: messageData.data.notification.description }}
            ></div>
          ),
        });
      }
    }
  }, [messageData]);
  return (
    <>
      {messageHolder}
      {notificationHolder}
      {messageData?.data?.modalForm && (
        <ConfirmForm
          trigger={<></>}
          open={modalFormOpen}
          onOpen={(open) => {
            setModalFormOpen(open);
          }}
          page={messageData?.data?.modalForm?.page}
          msg={messageData?.data?.modalForm?.title}
          key="modal"
        />
      )}
      {messageData?.data?.modalTable && (
        <ButtonModal
          open={modalTableOpen}
          afterOpenChange={(open) => {
            setModalTableOpen(open);
          }}
          key="tablemodal"
          trigger={<></>}
          title={messageData?.data?.modalTable?.title}
          modalProps={{ footer: null }}
        >
          <TableFromBread
            fieldProps={{ path: messageData?.data?.modalTable?.page }}
            record={{}}
            alwaysenable={true}
          />
        </ButtonModal>
      )}
    </>
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