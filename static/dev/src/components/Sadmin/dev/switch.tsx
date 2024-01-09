import { useModel } from '@umijs/max';
import { Switch } from 'antd';
import { useState } from 'react';

const DevSwitch = () => {
  const [checked, setChecked] = useState(true);
  const { initialState, setInitialState } = useModel('@@initialState');
  return initialState?.settings?.dev ? (
    <Switch
      checkedChildren="调试"
      unCheckedChildren="调试"
      onChange={(checked) => {
        setChecked(checked);
        //   const theme = !checked ? 'light' : 'realDark';
        //   const token = !checked ? { ...lightDefaultToken } : { sider: {}, header: {} };
        setInitialState((s) => ({
          ...s,
          settings: {
            ...s?.settings,
            devDisable: !checked,
          },
        }));
      }}
      checked={checked}
    />
  ) : null;
};
export default DevSwitch;
