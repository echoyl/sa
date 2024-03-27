import request from '@/services/ant-design-pro/sadmin';
import React, { useMemo, useState } from 'react';

interface RequestParamProps {
  method: 'post' | 'get';
  url?: string;
  data?: { [key: string]: any };
}
interface RequestComponentProps {
  trigger?: JSX.Element;
  requestParam: RequestParamProps;
  callback?: (ret: any) => void;
}

const RequestComponent: React.FC<RequestComponentProps> = (props) => {
  const { trigger, requestParam, callback } = props;

  const [loading, setLoading] = useState(false);

  const { method = 'post', url = '', data } = requestParam;

  const doRequest = async () => {
    setLoading(true);
    const ret = await request[method]?.(url, {
      data: { ...data },
      then: () => {},
    });
    callback?.(ret);
    setLoading(false);
  };

  //   const triggerDom = useMemo(() => {
  //     if (!trigger) {
  //       return null;
  //     }

  //     return React.cloneElement(trigger, {
  //       key: 'trigger',
  //       loading,
  //       ...trigger.props,
  //       onClick: async (e: any) => {
  //         doRequest();
  //       },
  //     });
  //   }, [trigger]);

  const triggerDom = trigger
    ? React.cloneElement(trigger, {
        key: 'trigger',
        loading,
        ...trigger.props,
        onClick: async (e: any) => {
          doRequest();
        },
      })
    : null;

  return <>{triggerDom}</>;
};

export default RequestComponent;
